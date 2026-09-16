<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use App\Models\FoodDish;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf as PDF;


class PDFExportController extends Controller
{
    public function export(Request $request, $model)
    {

        // Initialize variables
        $data = null;
        $columns = [];
        $table = '';
        $fileName = "{$model}.pdf"; // Default file name

        // Determine the model class and table name
        switch ($model) {
            case 'customers':
                $query = User::query()
                    ->where('is_admin', 0); // ✅ exclude admin users

                // optional filters
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }

                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }

                $data = $query->get()->map(function($user) {
                    // ✅ Get selected address
                    $address = \DB::table('user_addresses')
                        ->where('user_id', $user->id)
                        ->where('is_selected', 1)
                        ->value('full_address'); // sirf ek address fetch kare

                    return (object)[
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'gender' => $user->gender,
                        'dob' => $user->dob,
                        'address' => $address ?? '-', 
                        'date' => \Carbon\Carbon::parse($user->created_at)->format('d-m-Y'),
                    ];
                });

                // ✅ Columns
                $columns = ['name', 'email', 'phone_number', 'gender', 'dob', 'address', 'date'];
                break;


            case 'chefs':
                $query = Chef::query();
            
                // optional filters (agar future me date filters use karne ho)
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
            
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
            
                $data = $query->select('name', 'email', 'phone_number','kitchen_name','address' ,'city','pincode','fssai_license_number','fssai_validity_date','bank_name','account_number','ifsc_code','pan_card', \DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"))->get();
                $columns = ['name', 'email', 'phone_number','kitchen_name','address' ,'city','pincode','fssai_license_number','fssai_validity_date','bank_name','account_number','ifsc_code','pan_card','date'];
                
                break;


            case 'users':
                $query = User::query()
                    ->where('is_admin', 0); // ✅ exclude admin users

                // optional filters
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }

                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }

                $data = $query->get()->map(function($user) {
                    // ✅ Get selected address
                    $address = \DB::table('user_addresses')
                        ->where('user_id', $user->id)
                        ->where('is_selected', 1)
                        ->value('full_address'); // sirf ek address fetch kare

                    return (object)[
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'gender' => $user->gender,
                        'dob' => $user->dob,
                        'address' => $address ?? '-', 
                        'date' => \Carbon\Carbon::parse($user->created_at)->format('d-m-Y'),
                    ];
                });

                // ✅ Columns
                $columns = ['name', 'email', 'phone_number', 'gender', 'dob', 'address', 'date'];
            break;





            case 'food_dishes':
                $query = FoodDish::query();

                // ✅ Chef filter (agar chef_id diya gaya ho)
                if ($request->filled('chef_id')) {
                    $query->where('chef_id', $request->chef_id);
                }

                // ✅ Optional date filters
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }

                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }

                // ✅ Fetch data
                $data = $query->select(
                    'id',
                    'name',
                    'description',
                    'price',
                    'spicy_level',
                    'weight_option_id',
                    'preparation_time_id',
                    'ingredients',
                    'allergy_warning',
                    'in_stock',
                    'is_recommended',
                    'category_id',
                    'cuisine_type_id',
                    'tags',
                    \DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date")
                )->get();

                // ✅ Convert IDs into Names
                $data = $data->map(function ($item) {
                    // 🔹 Category Title
                    if (!empty($item->category_id)) {
                        $categoryIds = explode(',', $item->category_id);
                        $categories = \App\Models\Category::whereIn('id', $categoryIds)->pluck('title')->toArray();
                        $item->category_titles = implode(', ', $categories);
                    } else {
                        $item->category_titles = '-';
                    }

                    // 🔹 Cuisine Type Title
                    if (!empty($item->cuisine_type_id)) {
                        $cuisine = \App\Models\CuisineType::where('id', $item->cuisine_type_id)->value('title');
                        $item->cuisine_type_title = $cuisine ?? '-';
                    } else {
                        $item->cuisine_type_title = '-';
                    }

                    // 🔹 Tags Titles
                    if (!empty($item->tags)) {
                        $tagIds = explode(',', $item->tags);
                        $tags = \App\Models\Tag::whereIn('id', $tagIds)->pluck('title')->toArray();
                        $item->tag_titles = implode(', ', $tags);
                    } else {
                        $item->tag_titles = '-';
                    }

                    return $item;
                });

                // ✅ Columns to show in PDF
                $columns = ['name', 'description', 'price', 'spicy_level','weight_option_id','preparation_time_id','ingredients','allergy_warning','in_stock','is_recommended','category_titles', 'cuisine_type_title', 'tag_titles', 'date'];
                break;


            case 'chef_orders':
                    $query = Order::with('user'); // ✅ eager load user relation

                    // ✅ Chef filter (agar chef_id diya gaya ho)
                    if ($request->filled('chef_id')) {
                        $query->where('chef_id', $request->chef_id);
                    }

                    // ✅ Optional date filters
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }

                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }

                    // ✅ Orders fetch
                    $orders = $query->get();

                    // ✅ GST fetch from settings (example: "5%")
                    $gstSetting = \DB::table('settings')->value('gst');
                    $gstValue = (float) str_replace('%', '', $gstSetting ?? 0);

                    $data = $orders->map(function ($order) use ($gstValue) {

                        // ✅ Ensure items are array (safe decode)
                        $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
                        $itemDetails = [];
                        $subtotal = 0;

                        if (is_array($items)) {
                            foreach ($items as $item) {
                                $price = (float) ($item['price'] ?? 0);
                                $qty = (int) ($item['quantity'] ?? 0);
                                $subtotal += $price * $qty;

                                // ✅ Rupees symbol use UTF-8
                                $itemDetails[] = sprintf(
                                    "%s (₹%s × %s)",
                                    $item['name'] ?? 'Unknown',
                                    $price,
                                    $qty
                                );
                            }
                        }

                        // ✅ Calculate total including GST
                        $gstAmount = ($subtotal * $gstValue) / 100;
                        $totalAmount = $subtotal + $gstAmount;

                        // ✅ Safe decode for payment
                        $payment = is_string($order->payment) ? json_decode($order->payment, true) : $order->payment;

                        return (object)[
                            'order_id' => $order->id,
                            'user_name' => $order->user->name ?? 'N/A', // ✅ user ka name
                            'amount' => '₹' . round($totalAmount, 2), // ✅ total amount with GST + ₹
                            'status' => ucfirst($order->status ?? 'N/A'),
                            'payment_method' => $payment['method'] ?? 'N/A',
                            'payment_status' => $payment['status'] ?? 'N/A',
                            'items_details' => implode(', ', $itemDetails),
                            'get_now/get_later' => \Carbon\Carbon::parse($order->date)->format('d-m-Y'),
                            'date' => \Carbon\Carbon::parse($order->created_at)->format('d-m-Y'),
                        ];
                    });

                    // ✅ Columns for the PDF
                    $columns = [
                        'order_id',
                        'user_name',
                        'amount', // ✅ total including GST with ₹
                        'status',
                        'payment_method',
                        'payment_status',
                        'items_details',
                        'get_now/get_later',
                        'date'
                    ];
                break;



            case 'orders':
                $query = Order::with('user'); // ✅ eager load user relation

                // ✅ Optional date filters
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }

                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }

                // ✅ Orders fetch
                $orders = $query->get();

                // ✅ GST fetch from settings (example: "5%")
                $gstSetting = \DB::table('settings')->value('gst');
                $gstValue = (float) str_replace('%', '', $gstSetting ?? 0);

                $data = $orders->map(function ($order) use ($gstValue) {

                    // ✅ Ensure items are array (safe decode)
                    $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
                    $itemDetails = [];
                    $subtotal = 0;

                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $price = (float) ($item['price'] ?? 0);
                            $qty = (int) ($item['quantity'] ?? 0);
                            $subtotal += $price * $qty;

                            $itemDetails[] = sprintf(
                                "%s (₹%s × %s)",
                                $item['name'] ?? 'Unknown',
                                $price,
                                $qty
                            );
                        }
                    }

                    // ✅ Calculate total including GST
                    $gstAmount = ($subtotal * $gstValue) / 100;
                    $totalAmount = $subtotal + $gstAmount;

                    // ✅ Safe decode for payment
                    $payment = is_string($order->payment) ? json_decode($order->payment, true) : $order->payment;

                    return (object)[
                        'order_id' => $order->id,
                        'user_name' => $order->user->name ?? 'N/A',
                        'amount' => '₹' . round($totalAmount, 2),
                        'status' => ucfirst($order->status ?? 'N/A'),
                        'payment_method' => $payment['method'] ?? 'N/A',
                        'payment_status' => $payment['status'] ?? 'N/A',
                        'items_details' => implode(', ', $itemDetails),
                        'get_now/get_later' => \Carbon\Carbon::parse($order->date)->format('d-m-Y'),
                        'date' => \Carbon\Carbon::parse($order->created_at)->format('d-m-Y'),
                    ];
                });

                $columns = [
                    'order_id',
                    'user_name',
                    'amount',
                    'status',
                    'payment_method',
                    'payment_status',
                    'items_details',
                    'get_now/get_later',
                    'date'
                ];
            break;








            
            default:
                return abort(404);
        }

        // Generate PDF
        $pdf = PDF::loadView('pdf.generic', compact('data', 'columns', 'model'))->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ]);

        // Download PDF
        return $pdf->download($fileName);
    }
}
