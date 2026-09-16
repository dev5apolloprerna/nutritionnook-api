<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Helpers\CommonHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;



class InvoiceController extends Controller
{
    public function downloadInvoice(Request $request, $id)
    {
        $user = auth()->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Agar casts model me set hai to json_decode ki zarurat nahi hai
        $order->items   = is_string($order->items) ? json_decode($order->items, true) : $order->items;
        $order->payment = is_string($order->payment) ? json_decode($order->payment, true) : $order->payment;

        $pdf = Pdf::loadView('invoices.invoice', compact('order', 'user'));

        return $pdf->download('invoice-'.$order->id.'.pdf');
    }
    

    public function generateFilteredInvoice(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /* ============================
       VALIDATION
    ============================= */
    $request->validate([
        'from_date' => 'nullable|date',
        'to_date'   => 'nullable|date',
        'status'    => 'nullable|string',
        'search'    => 'nullable|string',
    ]);

    $fromDate = $request->from_date;
    $toDate   = $request->to_date;
    $status   = $request->status;
    $search   = $request->search;
    
    $incompleteStatuses = ['new', 'accepted', 'preparing', 'ready'];

    /* ============================
       BASE QUERY
    ============================= */
    $query = DB::table('orders as o')
        ->join('users as u', 'o.user_id', '=', 'u.id')
        ->select(
            'o.id',
            'o.items',
            'o.amount',
            'o.status',
            'o.created_at',
            'o.chef_id',
            'u.name as customer_name'
        )
        ->where('o.chef_id', $user->id);

    /* ============================
       DATE FILTER (AS REQUESTED)
    ============================= */
    if ($fromDate && $toDate) {
        $query->whereBetween(
            DB::raw('DATE(o.created_at)'),
            [$fromDate, $toDate]
        );
    }

    /* ============================
       STATUS FILTER
    ============================= */
    if ($status && $status !== 'all') {

    if ($status === 'incomplete') {
        $query->whereIn('o.status', $incompleteStatuses);
    } else {
        $query->where('o.status', $status);
    }
}

    /* ============================
       SEARCH FILTER (ANYTHING)
    ============================= */
    if ($search) {
        $query->where(function ($q) use ($search) {

            if (is_numeric($search)) {
                $q->orWhere('o.id', $search)
                  ->orWhere('o.amount', $search);
            }

            $q->orWhere('u.name', 'like', "%$search%")
              ->orWhere('o.items', 'like', "%$search%")
              ->orWhereDate('o.created_at', $search);
        });
    }

    $orders = $query->orderBy('o.created_at', 'desc')->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No orders found'
        ], 404);
    }

    /* ============================
       FORMAT ITEMS
    ============================= */
    foreach ($orders as $order) {
        $order->items = is_string($order->items)
            ? json_decode($order->items, true)
            : $order->items;

        $order->items = is_array($order->items) ? $order->items : [];
    }

    /* ============================
       GST
    ============================= */
    $gstPercent = (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);

    $subtotal = $orders->sum('amount');
    $gstAmount = ($subtotal * $gstPercent) / 100;

    /* ============================
       PDF DATA
    ============================= */
    $data = [
        'orders'     => $orders,
        'user'       => $user,
        'subtotal'   => $subtotal,
        'gstPercent' => $gstPercent,
        'gstAmount'  => $gstAmount,
        'filters'    => $request->only(['from_date','to_date','status','search']),
        'date'       => now()->format('d M Y'),
    ];

    $pdf = \PDF::loadView('invoices.filtered-orders', $data);

    $fileName = 'orders_invoice_' . time() . '.pdf';
    $path = public_path('invoices');

    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

   
    $pdf->save($path . '/' . $fileName);

    $data = [
        'invoice_url' => url('public/invoices/' . $fileName)
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'order history generated successfully.',
        $data
    );

}


}
