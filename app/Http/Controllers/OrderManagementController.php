<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Chef;
use App\Models\User;
use Mail;
use App\Models\Refund;
use App\Services\FCMService;
use App\Models\FoodDish;
use DB;
use Illuminate\Http\Request;
use App\Services\RefundService;

class OrderManagementController extends Controller
{
    // public function index()
    // {
    //      // Orders with chef & customer relations
    //     $orders = Order::with(['chef', 'user'])->latest()->get();

    //     // Convert items JSON into readable string
    //     foreach ($orders as $order) {
    //         $itemDetails = [];

    //         foreach ($order->items as $item) {
    //             $dish = FoodDish::find($item['id']); // food_dishes table
    //             if ($dish) {
    //                 $itemDetails[] = $dish->name . ' (' . $item['quantity'] . ')';
    //             }
    //         }

    //         $order->items_text = implode(', ', $itemDetails); // extra field for blade
    //     }

    //     return view('admin.orders.list', compact('orders'));
    // }
    
    public function index(Request $request)
{
    // Status filter aaya to filter karo
    $status = $request->get('status');

    $query = Order::with(['chef', 'user'])
        ->where('payment_status', '!=', 'pending') // ✅ pending skip
        ->latest();

    if ($status && $status !== 'all') {
        $query->where('status', $status);
    }

    $orders = $query->get();

    foreach ($orders as $order) {
        // ✅ Agar items array nahi hai to decode karo
        $itemsRaw = is_array($order->items) ? $order->items : json_decode($order->items, true);

        $itemDetails = [];
        $subtotal = 0;

        if (is_array($itemsRaw)) {
            foreach ($itemsRaw as $item) {
                $dish = FoodDish::find($item['id']);
                if ($dish) {
                    $qty = $item['quantity'] ?? 1;
                    $price = $dish->price ?? 0;
                    $subtotal += $price * $qty;
                    $itemDetails[] = $dish->name . ' (' . $qty . ')';
                }
            }
        }

        $order->items_text = implode(', ', $itemDetails);

        // ✅ Delivery Fee (optional — static)
        $delivery_fee = 0;

        // ✅ GST setting (database se fetch)
        $gstSetting = \DB::table('settings')->value('gst'); // Example: "5%"
        $gstValue = (float) str_replace('%', '', $gstSetting ?? 0);

        // ✅ GST amount calculate
        $gstAmount = ($subtotal * $gstValue) / 100;

        // ✅ Total amount
        $total = $subtotal + $delivery_fee + $gstAmount;
        
        $platformFee = \DB::table('settings')->first()->platform_fee ?? 0;
        
       
        $total += $platformFee;

        // ✅ Assign total for Blade file
        $order->total_amount = round($total);
    }

    return view('admin.orders.list', compact('orders', 'status'));
}


    // public function show($id)
    // {
    //     // Sample data - in real app, you'd fetch from database
    //     $orderDetails = [
    //         'ORD-101' => [
    //             'customer' => [
    //                 'name' => 'Raj Malhotra',
    //                 'phone' => '9876543210',
    //                 'email' => 'raj.malhotra@example.com',
    //                 'avatar' => 'https://randomuser.me/api/portraits/men/75.jpg',
    //                 'address' => '201, Sunshine Apartments, Andheri East',
    //                 'landmark' => 'Near Infinity Mall, Mumbai - 400069'
    //             ],
    //             'items' => [
    //                 ['name' => 'Butter Chicken', 'qty' => 2, 'price' => '₹250', 'total' => '₹500'],
    //                 ['name' => 'Garlic Naan', 'qty' => 4, 'price' => '₹30', 'total' => '₹120'],
    //                 ['name' => 'Jeera Rice', 'qty' => 2, 'price' => '₹120', 'total' => '₹240']
    //             ],
    //             'subtotal' => '₹860',
    //             'delivery_fee' => '₹40',
    //             'tax' => '₹86',
    //             'total' => '₹986',
    //             'instructions' => 'Make it less spicy please',
    //             'delivery_boy' => [
    //                 'name' => 'Rahul Kumar',
    //                 'phone' => '8765432109',
    //                 'avatar' => 'https://randomuser.me/api/portraits/men/45.jpg',
    //                 'rating' => 4.5,
    //                 'vehicle' => 'Bike - Honda Activa (MH01 AB 1234)'
    //             ],
    //             'tracking' => [
    //                 ['status' => 'Order Received', 'time' => 'Today, 12:30 PM', 'active' => true],
    //                 ['status' => 'Preparing by Chef', 'time' => 'Today, 12:45 PM', 'active' => true],
    //                 ['status' => 'Picked up by Delivery', 'time' => 'Today, 1:00 PM', 'active' => true],
    //                 ['status' => 'On the way', 'time' => 'Current Location: Andheri East', 'active' => true],
    //                 ['status' => 'Delivered', 'time' => 'Estimated: 1:40 PM', 'active' => false]
    //             ]
    //         ],
    //         'ORD-102' => [
    //             'customer' => [
    //                 'name' => 'Priya Sharma',
    //                 'phone' => '8765432109',
    //                 'email' => 'priya.sharma@example.com',
    //                 'avatar' => 'https://randomuser.me/api/portraits/women/68.jpg',
    //                 'address' => '502, Green Valley Apartments, Bandra West',
    //                 'landmark' => 'Near Linking Road, Mumbai - 400050'
    //             ],
    //             'items' => [
    //                 ['name' => 'Paneer Butter Masala', 'qty' => 1, 'price' => '₹220', 'total' => '₹220'],
    //                 ['name' => 'Plain Naan', 'qty' => 2, 'price' => '₹25', 'total' => '₹50'],
    //                 ['name' => 'Mango Lassi', 'qty' => 1, 'price' => '₹60', 'total' => '₹60']
    //             ],
    //             'subtotal' => '₹330',
    //             'delivery_fee' => '₹40',
    //             'tax' => '₹33',
    //             'total' => '₹403',
    //             'instructions' => 'Extra napkins please',
    //             'delivery_boy' => [
    //                 'name' => 'Vikram Singh',
    //                 'phone' => '7654321098',
    //                 'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
    //                 'rating' => 4.2,
    //                 'vehicle' => 'Bike - TVS Jupiter (MH02 CD 4567)'
    //             ],
    //             'tracking' => [
    //                 ['status' => 'Order Received', 'time' => 'Today, 1:15 PM', 'active' => true],
    //                 ['status' => 'Preparing by Chef', 'time' => 'Today, 1:30 PM', 'active' => true],
    //                 ['status' => 'Ready for Pickup', 'time' => 'Today, 1:45 PM', 'active' => false],
    //                 ['status' => 'Picked up by Delivery', 'time' => 'Estimated: 2:00 PM', 'active' => false],
    //                 ['status' => 'Delivered', 'time' => 'Estimated: 2:30 PM', 'active' => false]
    //             ],
    //             'time' => '16 Jul, 1:15 PM'
    //         ]
    //     ];

    //     $order = $orderDetails[$id] ?? abort(404);
    //     return view('admin.orders.show', compact('order', 'id'));
    // }
    
public function refund(Request $request)
{
    
    $request->validate([
        'order_id' => 'required|exists:orders,id',
    ]);

    $order = Order::findOrFail($request->order_id);
    

    try {

        // ── Step 1: Force 100% refund ────────────────────────────
        $refundPercentage = 100;
        $refundMessage = 'Your refund has been successfully initiated and will be credited within 5-7 business days.';

        // Optional: store in DB
        $order->refund_percentage = $refundPercentage;
        $order->rejected_by = 'admin'; // or 'system'
        $order->save();

        // ── Step 2: Prepare shared data (SAME as rejected flow) ──
        $user  = DB::table('users')->where('id', $order->user_id)->first();
        $chef  = DB::table('chefs')->where('id', $order->chef_id)->first();

        $items = is_array($order->items) 
            ? $order->items 
            : json_decode($order->items, true);

        $items     = $items ?? [];
        $foodImage = $this->getOrderFoodImage($items);
        $logo      = $this->getAppLogo();

        // ── Step 3: Notifications ────────────────────────────────
        $this->sendRejectionNotifications(
            $order,
            $user,
            $chef,
            $items,
            $refundPercentage,
            $refundMessage,
            $logo,
            $foodImage
        );

        // ── Step 4: Emails ───────────────────────────────────────
        $this->sendRejectionEmails(
            $order,
            $user,
            $chef,
            $items,
            $refundPercentage,
            $refundMessage
        );

        // ── Step 5: Process refund (IMPORTANT) ───────────────────
        $this->processRefund(
            $order,
            $user,
            $refundPercentage,
            $refundMessage
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund initiated successfully. 100% amount will be credited to the customer.',
            'refund_percentage' => $refundPercentage,
            'refund_message' => $refundMessage,
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}


public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $api->utility->verifyWebhookSignature($payload, $signature, config('services.razorpay.webhook_secret'));
        } catch (\Exception $e) {
            return response()->json(['status'=>'invalid'], 400);
        }

        $event = json_decode($payload, true);

        // Refund success
        if ($event['event'] === 'refund.processed') {
            $refundId = $event['payload']['refund']['entity']['id'];
            Refund::where('razorpay_refund_id', $refundId)
                ->update(['status' => 'success']);
        }

        // Refund failed
        if ($event['event'] === 'refund.failed') {
            $refundId = $event['payload']['refund']['entity']['id'];
            Refund::where('razorpay_refund_id', $refundId)
                ->update([
                    'status' => 'failed',
                    'reason' => 'Refund failed by Razorpay',
                ]);
        }

        return response()->json(['status'=>'ok']);
    }

    public function show($id)
{
    // Order fetch karo
    $order = Order::with(['chef', 'user'])->findOrFail($id);

    // Items handle karo
    $itemsRaw = is_array($order->items) ? $order->items : json_decode($order->items, true);
    $items = [];
    foreach ($itemsRaw as $item) {
        $price = (float) ($item['price'] ?? 0);
        $qty   = (int) ($item['quantity'] ?? 1);
        $items[] = [
            'name'  => $item['name'] ?? 'Unknown Item',
            'qty'   => $qty,
            'price' => $price,
            'total' => $price * $qty,
            'image' => $item['image'] ?? asset('images/placeholder.png'),
        ];
    }

    // --- Price Calculation (Database mathi direct lo) ---
    $subtotal    = array_sum(array_column($items, 'total'));
    $platformFee = (float) ($order->platform_fee ?? 0); // Database mathi order specific fee
    $gstAmount   = (float) ($order->calculated_gst ?? 0); // Database mathi direct GST amount
    $gstSetting  = \DB::table('settings')->value('gst') ?? '5%'; 
    
    // Final Amount direct order table mathi lo, calculation ma bhul na thay
    $total = (float) $order->amount; 

    // --- Payment Details ---
    $payment = is_array($order->payment) ? $order->payment : json_decode($order->payment, true);
    $payment = $payment ?? [
        'method' => $order->payment_method ?? 'Unknown',
        'status' => $order->payment_status ?? 'N/A',
        'paidAt' => $order->paid_at
    ];
        
    // Address logic
    $selectedAddress = \DB::table('user_addresses')
        ->where('user_id', $order->user_id)
        ->where('is_selected', 1)
        ->first();

    $customer = [
        'name'    => $order->user->name ?? 'Guest User',
        'email'   => $order->user->email ?? 'guest@example.com',
        'phone'   => $order->user->phone_number ?? 'N/A',
        'avatar'  => asset('/images/user-image.png'),
        'address' => $selectedAddress->full_address ?? ($order->address ?? 'No Address Found'),
        'pincode' => $selectedAddress->pincode ?? 'N/A',
        'tag'     => $selectedAddress->tag ?? 'N/A',
    ];

    $deliveryBoy = [
        'name'   => 'John Doe',
        'avatar' => asset('/images/user-image.png'),
        'phone'  => '9876543210',
        'vehicle'=> 'Bike',
        'rating' => 4.5,
    ];

    $history = \DB::table('order_status_histories')
        ->where('order_id', $order->id)
        ->orderBy('created_at', 'asc')
        ->get();

    $tracking = $history->map(function ($row, $index) use ($history) {
        return [
            'status' => ucfirst($row->status),
            'time'   => \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A'),
            'active' => $index == $history->count() - 1,
        ];
    });

    return view('admin.orders.show', compact('order', 'items', 'subtotal', 'platformFee', 'total', 'payment', 'customer', 'deliveryBoy', 'gstAmount','gstSetting','tracking','selectedAddress'));
}
    
     private function calculateRefund(object $order, int $isPreOrder): array
    {
        $now       = Carbon::now();
        $orderDate = Carbon::parse($order->date);

        if ($isPreOrder === 0) {
            // Regular order: 100% refund only if cancelled within 2 minutes of placing
            $secondsSincePlaced = $orderDate->diffInSeconds($now);

            if ($secondsSincePlaced <= 120) {
                return [
                    'percentage' => 100,
                    'message'    => '100% refund has been initiated.',
                ];
            }

            return [
                'percentage' => 0,
                'message'    => 'No refund applicable. Order was placed more than 2 minutes ago.',
            ];
        }

        // Pre-order: based on how many hours remain until the order date
        $hoursUntilOrder = $now->diffInHours($orderDate, false);
        // diffInHours with false: positive = orderDate is in future, negative = past

        if ($hoursUntilOrder >= 48) {
            return [
                'percentage' => 100,
                'message'    => '100% refund has been initiated.',
            ];
        }

        if ($hoursUntilOrder >= 24) {
            return [
                'percentage' => 50,
                'message'    => '50% refund has been initiated.',
            ];
        }

        return [
            'percentage' => 0,
            'message'    => 'No refund applicable. Pre-order cancelled within 24 hours.',
        ];
    }
    
    private function buildNotificationPayload(object $order, array $items, string $title, string $body, string $type, int $refundPercentage, string $logo, string $foodImage): array
    {
        return [
            'title'             => $title,
            'body'              => $body,
            'type'              => $type,
            'orderId'           => (string) $order->id,
            'amount'            => (string) $order->amount,
            'items'             => (string) count($items),
            'refund_percentage' => (string) $refundPercentage,
            'logo'              => $logo,
            'food_image'        => $foodImage,
        ];
    }
    
     private function getOrderFoodImage(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $firstItemId = $items[0]['id'] ?? null;
        if (!$firstItemId) {
            return '';
        }

        $dishImage = DB::table('food_dishes')->where('id', $firstItemId)->value('image');

        return $dishImage ? url($dishImage) : '';
    }
    
     private function getAppLogo(): string
    {
        $setting = DB::table('settings')->first();
        return ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';
    }
    
    
    public function updateStatusOrderManagement(Request $request)
    {
        $request->validate([
            'order_id'     => 'required|exists:orders,id',
            'status'       => 'required|in:new,accepted,rejected,preparing,ready,delivered',
            'is_pre_order' => 'nullable|in:0,1', // must be sent when status = rejected
        ]);

        $order = Order::findOrFail($request->order_id);

        // ── Step 1: Calculate refund if rejecting ────────────────────────────
        $refundPercentage = 0;
        $refundMessage    = '';

        if ($request->status === 'rejected') {

            if ($request->has('is_pre_order')) {
        
                // ✅ Existing logic (no change)
                $refundData       = $this->calculateRefund($order, (int) $request->is_pre_order);
                $refundPercentage = $refundData['percentage'];
                $refundMessage    = $refundData['message'];
        
            } else {
        
                // 🔥 IMPORTANT FIX → when is_pre_order NOT sent
                $refundPercentage = 100;
                $refundMessage = 'Your refund has been successfully initiated and will be credited within 5-7 business days.';
            }
        
            $order->refund_percentage = $refundPercentage;
            $order->rejected_by       = 'admin'; // or dynamic
        }

        $order->status = $request->status;
        $order->save();

        // ── Step 2: Prepare shared data ──────────────────────────────────────
        $user         = DB::table('users')->where('id', $order->user_id)->first();
        $chef         = DB::table('chefs')->where('id', $order->chef_id)->first();
        $customerName = $user->name ?? 'Customer';
        $chefName     = $chef->name ?? 'Chef';
        $items        = is_array($order->items) ? $order->items : json_decode($order->items, true);
        $items        = $items ?? [];
        $foodImage    = $this->getOrderFoodImage($items);
        $logo         = $this->getAppLogo();

        // ── Step 3: Push notifications ───────────────────────────────────────
        if ($request->status === 'rejected') {
            $this->sendRejectionNotifications(
                $order, $user, $chef, $items,
                $refundPercentage, $refundMessage,
                $logo, $foodImage
            );
        } else {
            $this->sendStatusUpdateNotification(
                $order, $items, $refundPercentage, $logo, $foodImage
            );
        }

        // ── Step 4: Send emails ──────────────────────────────────────────────
        if ($request->status === 'rejected') {
            $this->sendRejectionEmails(
                $order, $user, $chef, $items,
                $refundPercentage, $refundMessage
            );
        } else {
            $this->sendStatusUpdateEmail($order, $user);
        }

        // ── Step 5: Process refund if applicable ─────────────────────────────
        if ($request->status === 'rejected' && $refundPercentage > 0) {
            $this->processRefund($order, $user, $refundPercentage, $refundMessage);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'Order status updated successfully!',
            'refund_percentage' => $refundPercentage,
            'refund_message'    => $refundMessage,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Send rejection push to BOTH customer AND chef
    // ─────────────────────────────────────────────────────────────────────────

    private function sendRejectionNotifications(
        Order $order,
        $user,
        $chef,
        array $items,
        int $refundPercentage,
        string $refundMessage,
        string $logo,
        string $foodImage
    ): void {
        $fcm = new FCMService();

        // ── Push to Customer ─────────────────────────────────────────────────
        try {
            $customerTitle = 'Your Order Has Been Cancelled';
            $customerBody  = "Your order #{$order->id} has been rejected by the chef.";
            if ($refundPercentage > 0) {
                $customerBody .= " {$refundMessage}";
            }

            $fcm->sendNotificationToUser(
                $order->user_id,
                $customerTitle,
                $customerBody,
                $this->buildNotificationPayload(
                    $order, $items, $customerTitle, $customerBody,
                    'order_rejected_by_admin', $refundPercentage, $logo, $foodImage
                )
            );

            \Log::info('Rejection push sent to customer', [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Rejection push to customer failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // ── Push to Chef ─────────────────────────────────────────────────────
        try {
            $chefTitle = 'Order Cancelled by Chef';
            $chefBody  = "Order #{$order->id} has been cancelled by Chef.";

            $fcm->sendNotificationToUser(
                $order->chef_id,
                $chefTitle,
                $chefBody,
                $this->buildNotificationPayload(
                    $order, $items, $chefTitle, $chefBody,
                    'order_cancelled_by_admin_to_chef', 0, $logo, $foodImage
                )
            );

            \Log::info('Rejection push sent to chef', [
                'order_id' => $order->id,
                'chef_id'  => $order->chef_id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Rejection push to chef failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Send generic status update push (non-rejection)
    // ─────────────────────────────────────────────────────────────────────────

    private function sendStatusUpdateNotification(
        Order $order,
        array $items,
        int $refundPercentage,
        string $logo,
        string $foodImage
    ): void {
        try {
            $title = 'Order Status Updated';
            $body  = "Your order #{$order->id} status is now: {$order->status}.";

            (new FCMService())->sendNotificationToUser(
                $order->user_id,
                $title,
                $body,
                $this->buildNotificationPayload(
                    $order, $items, $title, $body,
                    'order_status_update', $refundPercentage, $logo, $foodImage
                )
            );

            \Log::info('Status update push sent', [
                'order_id' => $order->id,
                'status'   => $order->status,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Status update push failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Send rejection emails to customer + chef
    // ─────────────────────────────────────────────────────────────────────────

    private function sendRejectionEmails(
        Order $order,
        $user,
        $chef,
        array $items,
        int $refundPercentage,
        string $refundMessage
    ): void {
        // Email to customer
        try {
            if (!empty($user->email)) {
                Mail::send('emails.order_rejected_by_admin_customer', [
                    'name'             => $user->name ?? 'Customer',
                    'orderId'          => $order->id,
                    'items'            => $items,
                    'amount'           => $order->amount,
                    'order_date'       => $order->date,
                    'rejection_time'   => now()->format('d M Y, h:i A'),
                    'refund_percentage'=> $refundPercentage,
                    'refund_message'   => $refundMessage,
                ], function ($message) use ($user, $order) {
                    $message->to($user->email)
                            ->subject("Your Order #{$order->id} Has Been Cancelled");
                });
            }
        } catch (\Throwable $e) {
            \Log::error('Rejection email to customer failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Email to chef
       try {
    if ($chef && !empty($chef->email)) {

        \Log::info('Sending mail to chef', [
            'email' => $chef->email,
            'order_id' => $order->id
        ]);

        Mail::send('emails.order_rejected_by_admin_chef', [
            'chef'           => $chef,
            'orderId'        => $order->id,
            'items'          => $items ?? [],
            'amount'         => $order->amount ?? 0,
            'order_date'     => $order->date ?? now(),
            'rejection_time' => now()->format('d M Y, h:i A')
        ], function ($message) use ($chef, $order) {
            $message->to($chef->email)
                    ->subject("Order #{$order->id} Cancelled by Admin");
        });

        \Log::info('Chef mail sent successfully');

    } else {
        \Log::error('Chef email missing', [
            'chef' => $chef
        ]);
    }

} catch (\Throwable $e) {
    \Log::error('Chef mail failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    dd($e->getMessage()); // temporary debug
}
    }
    
    
    
    
   private function sendDeliveredEmails(
    $order,
    $user,
    $chef,
    array $items,
    int $refundPercentage,
    string $refundMessage
): void {
    
    try {
        if (!empty($user->email)) {
            // --- Price Calculation Logic ---
            $subtotal = collect($items)->sum(function($item) {
                return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            });
            
            
            $gstPercent = (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);
            $gstAmount = (float) ($order->calculated_gst ?? (($subtotal * $gstPercent) / 100));
            $platformFee = (float) ($order->platform_fee ?? 0);
            $discountAmount = (float) ($order->discount_amount ?? 0);

            Mail::send('emails.order_delivered_by_admin_customer', [
                'name'              => $user->name ?? 'Customer',
                'orderId'           => $order->id,
                'items'             => $items,
                'amount'            => $order->amount,
                'subtotal'          => $subtotal,
                'gstPercent'        => $gstPercent,
                'gstAmount'         => $gstAmount,
                'platformFee'       => $platformFee,
                'discountAmount'    => $discountAmount,
                'order_date'        => $order->date,
                'rejection_time'    => now()->format('d M Y, h:i A'),
                'refund_percentage' => $refundPercentage,
                'refund_message'    => $refundMessage,
            ], function ($message) use ($user, $order) {
                $message->to($user->email)
                        ->subject("Your Order #{$order->id} Has Been Delivered");
            });

            Log::info('done 100%');
        }
    } catch (\Throwable $e) {
        Log::error('delivered email to customer failed', [
            'order_id' => $order->id,
            'error'    => $e->getMessage(),
        ]);
    }
}

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Send generic status update email to customer
    // ─────────────────────────────────────────────────────────────────────────

    private function sendStatusUpdateEmail(Order $order, $user): void
    {
        try {
            if ($user && !empty($user->email)) {
                Mail::send('emails.order-status-update-one', [
                    'name'    => $user->name ?? 'Customer',
                    'orderId' => $order->id,
                    'status'  => $order->status,
                    'amount'  => $order->amount,
                ], function ($message) use ($user, $order) {
                    $message->to($user->email)
                            ->subject("Order #{$order->id} Status Updated");
                });
            }
        } catch (\Throwable $e) {
            \Log::error('Status update email failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Process Razorpay refund + send refund push + refund email
    // ─────────────────────────────────────────────────────────────────────────

    private function processRefund(Order $order, $user, int $refundPercentage, string $refundMessage): void
    {
        try {
            // Re-fetch fresh Eloquent model (service expects Eloquent, not DB result)
            $freshOrder = Order::findOrFail($order->id);
            $refund     = (new RefundService())->refundOrder($freshOrder);

            \Log::info('Refund processed successfully', [
                'order_id'          => $order->id,
                'refund_id'         => $refund->id,
                'refund_amount'     => $refund->refund_amount,
                'refund_percentage' => $refundPercentage,
            ]);

            // ── Refund push to customer ──────────────────────────────────────
            try {
                $refundTitle = 'Refund Initiated';
                $refundBody  = "Your {$refundPercentage}% refund of ₹{$refund->refund_amount} for order #{$order->id} is being processed. It will reflect in 5-7 business days.";

                (new FCMService())->sendNotificationToUser(
                    $order->user_id,
                    $refundTitle,
                    $refundBody,
                    [
                        'title'             => $refundTitle,
                        'body'              => $refundBody,
                        'type'              => 'refund_initiated',
                        'orderId'           => (string) $order->id,
                        'refund_percentage' => (string) $refundPercentage,
                        'refund_amount'     => (string) $refund->refund_amount,
                    ]
                );
            } catch (\Throwable $e) {
                \Log::warning('Refund push notification failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            // ── Refund email to customer ─────────────────────────────────────
            try {
                if ($user && !empty($user->email)) {
                    Mail::send('emails.refund_initiated', [
                        'name'              => $user->name ?? 'Customer',
                        'orderId'           => $order->id,
                        'refund_percentage' => $refundPercentage,
                        'refund_amount'     => $refund->refund_amount,
                        'refund_message'    => $refundMessage,
                    ], function ($message) use ($user, $order) {
                        $message->to($user->email)
                                ->subject("Refund Initiated for Order #{$order->id}");
                    });
                }
            } catch (\Throwable $e) {
                \Log::error('Refund email failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

        } catch (\Throwable $e) {
            // Refund failure must NOT block the API response — order is already rejected
            \Log::error('Refund processing failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }



}
