<?php

use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\SmtpSettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FoodDishController;
use App\Http\Controllers\FoodItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MealTimesController;
use App\Http\Controllers\CuisineTypeController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\RadiusSettingController;
use App\Http\Controllers\ChefManagementController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\RestaurantTypeController;
use App\Http\Controllers\PagesController;
use App\Models\CoreSyllabus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\TagController;
use App\Http\Controllers\CustomerManagementController;
use App\Http\Controllers\HomeScreenController;
use App\Http\Controllers\ContinuousAuditsController;
use App\Http\Controllers\AllergyController;
use App\Http\Controllers\KitchenPhotosController;
use App\Http\Controllers\PDFExportController;
use Carbon\Carbon;
use App\Services\FCMService;
use App\Http\Controllers\AdminNotificationController;
use App\Models\Order;

if (app()->environment('local')) {
    Route::get('/test-cron', function () {
        \Artisan::call('schedule:run');
        return 'Cron triggered';
    });
}

// NOT dev scaffolding — confirmed live in production's cPanel cron (daily at
// midnight: `curl https://api.nutritionnook.net/delete-pending-orders`), cleans
// up stale unpaid orders. Do not gate this behind environment('local').
Route::get('/delete-pending-orders', function () {

    $deleted = Order::where('payment_status', 'pending')->delete();

    return "✅ Deleted {$deleted} pending orders successfully.";
});

// Route::get('/update-buffer-status', function () {

//     \Log::info('cron start here');

//     $time = \Carbon\Carbon::now()->subMinutes(2);

//   $orders = Order::with('user')
//     ->where('is_buffer', 0)
//     ->where('status', '!=', 'rejected')
//     ->where('created_at', '<=', $time)
//     ->get();

//     $updated = 0;

//     foreach ($orders as $order) {

//         try {

//             /* ---------- UPDATE BUFFER ---------- */
//             $order->is_buffer = 1;
//             $order->save();
//             $updated++;

//             /* ---------- CUSTOMER ---------- */
//             $user = $order->user;
//             $customerName = $user->name ?? 'Customer';

//             /* ---------- ITEMS (JSON COLUMN) ---------- */
//             $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
//             $items = $items ?? [];

//             $foodImage = null;

//             if (!empty($items)) {

//                 $firstItemId = $items[0]['id'] ?? null;

//                 if ($firstItemId) {

//                     $dishImage = DB::table('food_dishes')
//                         ->where('id', $firstItemId)
//                         ->value('image');

//                     if ($dishImage) {
//                         $foodImage = url($dishImage);
//                     }
//                 }
//             }

//             /* ---------- LOGO ---------- */
//             $setting = DB::table('settings')->first();
//             $logo = ($setting && $setting->logo)
//                 ? url('public/images/' . $setting->logo)
//                 : '';

//             /* ---------- ORDER TYPE ---------- */
//             $orderDate = \Carbon\Carbon::parse($order->date);

//             $orderType = $orderDate->isToday()
//                 ? 'today'
//                 : ($orderDate->isFuture() ? 'preorder' : 'past');

//             /* ---------- PUSH NOTIFICATION ---------- */
//             $title = 'New order recieved ✅';
//             $body  = "You have received a new order from {$customerName}";

//             $data = [
//                 'title' => $title,
//                 'body' => $body,
//                 'type' => 'payment_received',
//                 'orderId' => (string) $order->id,
//                 'customerName' => $customerName,
//                 'amount' => (string) $order->amount,
//                 'items' => (string) count($items),
//                 'logo' => $logo,
//                 'food_image' => $foodImage ?? '',
//                 'order_type' => $orderType
//             ];

//             $fcmService = new FCMService();

//             $result = $fcmService->sendNotificationToUser(
//                 $order->chef_id,
//                 $title,
//                 $body,
//                 $data
//             );

//             \Log::info('FCM Result', [
//                 'order_id' => $order->id,
//                 'result' => $result
//             ]);

//         } catch (\Throwable $e) {

//             \Log::warning('Chef push failed', [
//                 'order_id' => $order->id,
//                 'error' => $e->getMessage(),
//             ]);

//         }
//     }

//     return response()->json([
//         'success' => true,
//         'updated_records' => $updated
//     ]);

// });

// Route::get('/update-buffer-status', function () {

//     \Log::info('CRON START: update-buffer-status');

//     $time = \Carbon\Carbon::now()->subMinutes(2);
//     \Log::info('Checking orders created before', ['time' => $time]);

//     $orders = Order::with('user')
//         ->where('is_buffer', 0)
//         ->where('status', '!=', 'rejected')
//         ->where('created_at', '<=', $time)
//         ->get();

//     \Log::info('Orders fetched for processing', [
//         'total_orders' => $orders->count()
//     ]);

//     $updated = 0;

//     foreach ($orders as $order) {

//         \Log::info('Processing order', [
//             'order_id' => $order->id,
//             'chef_id' => $order->chef_id
//         ]);

//         try {

//             /* ---------- UPDATE BUFFER ---------- */
//             \Log::info('Updating buffer status', [
//                 'order_id' => $order->id
//             ]);

//             $order->is_buffer = 1;
//             $order->save();

//             \Log::info('Buffer status updated successfully', [
//                 'order_id' => $order->id
//             ]);

//             $updated++;

//             /* ---------- CUSTOMER ---------- */

//             $user = $order->user;
//             $customerName = $user->name ?? 'Customer';

//             \Log::info('Customer info fetched', [
//                 'order_id' => $order->id,
//                 'customer_name' => $customerName
//             ]);

//             /* ---------- ITEMS (JSON COLUMN) ---------- */

//             $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
//             $items = $items ?? [];

//             \Log::info('Order items parsed', [
//                 'order_id' => $order->id,
//                 'items_count' => count($items)
//             ]);

//             $foodImage = null;

//             if (!empty($items)) {

//                 $firstItemId = $items[0]['id'] ?? null;

//                 \Log::info('Fetching food image', [
//                     'order_id' => $order->id,
//                     'dish_id' => $firstItemId
//                 ]);

//                 if ($firstItemId) {

//                     $dishImage = DB::table('food_dishes')
//                         ->where('id', $firstItemId)
//                         ->value('image');

//                     if ($dishImage) {
//                         $foodImage = url($dishImage);

//                         \Log::info('Food image found', [
//                             'order_id' => $order->id,
//                             'image_url' => $foodImage
//                         ]);
//                     } else {
//                         \Log::warning('Food image not found', [
//                             'order_id' => $order->id,
//                             'dish_id' => $firstItemId
//                         ]);
//                     }
//                 }
//             }

//             /* ---------- LOGO ---------- */

//             \Log::info('Fetching application logo');

//             $setting = DB::table('settings')->first();

//             $logo = ($setting && $setting->logo)
//                 ? url('public/images/' . $setting->logo)
//                 : '';

//             /* ---------- ORDER TYPE ---------- */

//             $orderDate = \Carbon\Carbon::parse($order->date);

//             $orderType = $orderDate->isToday()
//                 ? 'today'
//                 : ($orderDate->isFuture() ? 'preorder' : 'past');

//             \Log::info('Order type determined', [
//                 'order_id' => $order->id,
//                 'order_type' => $orderType
//             ]);

//             /* ---------- PUSH NOTIFICATION ---------- */

//             $title = 'New order recieved ✅';
//             $body  = "You have received a new order from {$customerName}";

//             $data = [
//                 'title' => $title,
//                 'body' => $body,
//                 'type' => 'payment_received',
//                 'orderId' => (string) $order->id,
//                 'customerName' => $customerName,
//                 'amount' => (string) $order->amount,
//                 'items' => (string) count($items),
//                 'logo' => $logo,
//                 'food_image' => $foodImage ?? '',
//                 'order_type' => $orderType
//             ];

//             \Log::info('Sending push notification to chef', [
//                 'order_id' => $order->id,
//                 'chef_id' => $order->chef_id
//             ]);

//             $fcmService = new FCMService();

//             $result = $fcmService->sendNotificationToUser(
//                 $order->chef_id,
//                 $title,
//                 $body,
//                 $data
//             );

//             \Log::info('FCM Notification result', [
//                 'order_id' => $order->id,
//                 'result' => $result
//             ]);

//         } catch (\Throwable $e) {

//             \Log::error('Order processing failed', [
//                 'order_id' => $order->id,
//                 'error_message' => $e->getMessage(),
//                 'line' => $e->getLine()
//             ]);
//         }
//     }

//     \Log::info('CRON FINISHED', [
//         'total_orders_updated' => $updated
//     ]);

//     return response()->json([
//         'success' => true,
//         'updated_records' => $updated
//     ]);
// });

// routes/web.php — temporary test

if (app()->environment('local')) {
    Route::get('/test-background', function () {
        ignore_user_abort(true);
        set_time_limit(300);

        // Response user ne moklo - connection close karo
        ob_start();
        echo json_encode(['success' => true]);
        $size = ob_get_length();
        header("Content-Length: $size");
        header("Connection: close");
        ob_end_flush();
        flush();

        // Background ma chalu
        sleep(10);

        \Log::info('Background process worked at: ' . now());
    });
}

Route::get('/update-buffer-status', function (\Illuminate\Http\Request $request) {
    $secret = config('app.cron_secret');
    if ($secret && $request->query('token') !== $secret && $request->header('X-Cron-Secret') !== $secret) {
        abort(403, 'Invalid or missing cron token.');
    }


    \Log::info('CRON START: update-buffer-status');

    $time = \Carbon\Carbon::now()->subMinutes(2);
    \Log::info('Checking orders created before', ['time' => $time]);

    $orders = Order::with('user')
        ->where('is_buffer', 0)
        ->where('status', '!=', 'rejected')
        ->where('created_at', '<=', $time)
        ->get();

    \Log::info('Orders fetched for processing', [
    'total_orders' => $orders->count(),
    'order_ids' => $orders->pluck('id')->all()
]);

   

    $updated = 0;

    foreach ($orders as $order) {

        \Log::info('Processing order', [
            'order_id' => $order->id,
            'chef_id' => $order->chef_id
        ]);

        try {

            /* ---------- UPDATE BUFFER ---------- */
            \Log::info('Updating buffer status', [
                'order_id' => $order->id
            ]);

            $order->is_buffer = 1;
            $order->save();

            \Log::info('Buffer status updated successfully', [
                'order_id' => $order->id
            ]);

            $updated++;

            /* ---------- CUSTOMER ---------- */

            $user = $order->user;
            $customerName = $user->name ?? 'Customer';

            \Log::info('Customer info fetched', [
                'order_id' => $order->id,
                'customer_name' => $customerName
            ]);

            /* ---------- ITEMS (JSON COLUMN) ---------- */

            $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
            $items = $items ?? [];

            \Log::info('Order items parsed', [
                'order_id' => $order->id,
                'items_count' => count($items)
            ]);

            $foodImage = null;

            if (!empty($items)) {

                $firstItemId = $items[0]['id'] ?? null;

                \Log::info('Fetching food image', [
                    'order_id' => $order->id,
                    'dish_id' => $firstItemId
                ]);

                if ($firstItemId) {

                    $dishImage = DB::table('food_dishes')
                        ->where('id', $firstItemId)
                        ->value('image');

                    if ($dishImage) {
                        $foodImage = url($dishImage);

                        \Log::info('Food image found', [
                            'order_id' => $order->id,
                            'image_url' => $foodImage
                        ]);
                    } else {
                        \Log::warning('Food image not found', [
                            'order_id' => $order->id,
                            'dish_id' => $firstItemId
                        ]);
                    }
                }
            }

            /* ---------- LOGO ---------- */

            \Log::info('Fetching application logo');

            $setting = DB::table('settings')->first();

            $logo = ($setting && $setting->logo)
                ? url('public/images/' . $setting->logo)
                : '';

            /* ---------- ORDER TYPE ---------- */

            $orderDate = \Carbon\Carbon::parse($order->date);

            $orderType = $orderDate->isToday()
                ? 'today'
                : ($orderDate->isFuture() ? 'preorder' : 'past');

            \Log::info('Order type determined', [
                'order_id' => $order->id,
                'order_type' => $orderType
            ]);

            /* ---------- PUSH NOTIFICATION ---------- */

            if ($order->payment_status == 'received') {
                
              
                \Log::info('Payment received. Sending push notification', [
                    'order_id' => $order->id
                ]);

                $title = 'New order recieved ✅';
                $body  = "You have received a new order from {$customerName}";

                $data = [
                    'title' => $title,
                    'body' => $body,
                    'type' => 'payment_received',
                    'orderId' => (string) $order->id,
                    'customerName' => $customerName,
                    'amount' => (string) $order->amount,
                    'items' => (string) count($items),
                    'logo' => $logo,
                    'food_image' => $foodImage ?? '',
                    'order_type' => $orderType
                ];

                \Log::info('Sending push notification to chef', [
                    'order_id' => $order->id,
                    'chef_id' => $order->chef_id
                ]);

                $fcmService = new FCMService();

                $result = $fcmService->sendNotificationToUser(
                    $order->chef_id,
                    $title,
                    $body,
                    $data
                );

                \Log::info('FCM Notification result', [
                    'order_id' => $order->id,
                    'result' => $result
                ]);

            } else {

                \Log::info('Push notification skipped because payment not received', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status
                ]);
            }

        } catch (\Throwable $e) {

            \Log::error('Order processing failed', [
                'order_id' => $order->id,
                'error_message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    \Log::info('CRON FINISHED', [
        'total_orders_updated' => $updated
    ]);

    return response()->json([
        'success' => true,
        'updated_records' => $updated
    ]);
});
// routes/web.php


    // Reports Dashboard
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('admin.reports.index');
    
    // Order & Revenue Reports
    Route::get('/reports/order-revenue', [App\Http\Controllers\ReportController::class, 'orderRevenueReports'])->name('admin.reports.order-revenue');
    
    // Chef Performance Reports
    Route::get('/reports/chef-performance', [App\Http\Controllers\ReportController::class, 'chefPerformanceReports'])->name('admin.reports.chef-performance');
    
    // Customer Insights
    Route::get('/reports/customer-insights', [App\Http\Controllers\ReportController::class, 'customerInsights'])->name('admin.reports.customer-insights');
    
    // Menu & Cuisine Insights
    Route::get('/reports/menu-cuisine', [App\Http\Controllers\ReportController::class, 'menuCuisineInsights'])->name('admin.reports.menu-cuisine');
    
    // Platform Health
    Route::get('/reports/platform-health', [App\Http\Controllers\ReportController::class, 'platformHealth'])->name('admin.reports.platform-health');


if (app()->environment('local')) {
Route::get('/seed-chef-customer', function () {

    DB::beginTransaction();

    try {

        // ==============================
        // COMMON VALUES
        // ==============================
        $otp = 123456;
        $password = 'password123';

        // ==============================
        // CREATE 5 CHEFS
        // ==============================
        for ($i = 1; $i <= 5; $i++) {

            $email = "cheftest{$i}@test.com";
            $phone = "900000000{$i}";

            // CHEF TABLE
            DB::table('chefs')->insert([
                'name'                => "Chef {$i}",
                'business_name'       => "Chef Kitchen {$i}",
                'kitchen_type'        => 'Home Kitchen',
                'daily_capacity'      => rand(30, 80),
                'fssai_license_number'=> '1234567890123' . $i,
                'fssai_validity_date' => now()->addYear(),
                'bank_name'           => 'HDFC Bank',
                'account_number'      => '1234567890' . $i,
                'ifsc_code'           => 'HDFC0000123',
                'pan_card'            => 'ABCDE1234F',
                'email'               => $email,
                'address'             => "Chef Kitchen Address {$i}",
                'pincode'             => '380001',
                'phone_number'        => $phone,
                'available'           => 1,
                'personal_document_type'   => 'Aadhar',
                'personal_document_status' => 'approved',
                'fssai_status'        => 'approved',
                'self_declaration'    => 1,
                'self_declaration_status' => 'approved',
                'continuous_audits'   => "2026-01-01",
                'chef_training'       => "2026-01-01",
                'onboarding_kit_receipt' => "2026-01-01",
                'currency'            => 'INR',
                'otp'                 => $otp,
                'otp_verified'        => 1,
                'earnings'            => 0,
                'is_delete'           => 0,
                'dob'                 => '1995-01-01',
                'gender'              => 'male',
                'kitchen_name'        => "Kitchen {$i}",
                'opening_time'        => '09:00',
                'closing_time'        => '22:00',
                'latitude'            => '23.0225',
                'longitude'           => '72.5714',
                'working_days'        => json_encode(['Mon','Tue','Wed','Thu','Fri','Sat']),
                'commission'          => 10,
                'about_chef'          => 'Demo chef account',
                'city'                => 'Ahmedabad',
                'password'            => Hash::make($password),
                'cuisine_speciality'  => 'Indian',
                'preference_tags'     => json_encode(['Veg','Spicy']),
                'delivery_radius'     => 10,
                'is_verify'           => 1,
                'profile_image'       => 'test.png',
                'is_pre_order'        => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // ==============================
        // CREATE 5 CUSTOMERS
        // ==============================
        for ($i = 1; $i <= 5; $i++) {

            DB::table('users')->insert([
                'image'             => null,
                'name'              => "Customer {$i}",
                'email'             => "customertest{$i}@test.com",
                'email_verified_at' => now(),
                'password'          => Hash::make($password),
                'plain_password'    => $password,
                'user_role'         => '4',
                'is_open'           => 1,
                'is_admin'          => 0,
                'phone_number'      => "800000000{$i}",
                'otp'               => $otp,
                'otp_verified'      => 1,
                'otp_expires_at'    => now()->addMinutes(10),
                'address'           => "Customer Address {$i}",
                'remember_token'    => Str::random(10),
                'status'            => 1,
                'currency'          => 'INR',
                'dob'               => '1998-01-01',
                'gender'            => 'female',
                'is_delete'         => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        DB::commit();

        return response()->json([
            'status'  => true,
            'message' => '5 chefs & 5 customers seeded successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => false,
            'error'  => $e->getMessage()
        ], 500);
    }
});
}


Route::get('/', function () {
    return redirect('/login');
});

Route::get('/clear-cache', function() {
   \Artisan::call('optimize:clear');
   return response()->json(['message' => 'Cache cleared successfully!']);
})->middleware('auth');


Route::get('/trending-dishes/{userId}', function ($userId) {
    
    // Get selected user address
    $userAddress = DB::table('user_addresses')
        ->where('user_id', $userId)
        ->where('is_selected', 1)
        ->first();
        
   
    if (!$userAddress) {
        return response()->json(['message' => 'No address found for this user']);
    }

    // Fetch dishes from chefs with same pincode (dummy "nearby" logic)
    $dishes = DB::table('food_dishes as fd')
        ->join('chefs as c', 'fd.chef_id', '=', 'c.id')
        ->where('fd.is_active', 1)
        ->where('c.pincode', $userAddress->pincode)
        ->select(
            'fd.id',
            'fd.name as dish_name',
            'fd.description',
            'fd.price',
            'fd.image as dish_image',
            'c.name as chef_name',
            'c.business_name',
            'c.address as chef_address'
        )
        ->orderBy('fd.id', 'desc') // trending = latest for demo
        ->get();

    return response()->json([
        'user_address' => $userAddress->full_address,
        'trending_dishes' => $dishes,
    ]);
});

Route::delete('/addresses/{address}', [UsersController::class, 'destroyAddress'])->name('addresses.destroy');

if (app()->environment('local')) {
    Route::get('/test-notification', [App\Http\Controllers\Api\NotificationController::class, 'sendNotification']);
}

if (app()->environment('local')) {
Route::get('/add' , function(){
    $userId = DB::table('users')->insertGetId([
            'name' => 'alex',
            'email' => 'alex9872@example.com',
            'password' => bcrypt('password'),
            'user_role' => 5,
            'phone_number' => '1234567998',
            'address' => 'Near SG Highway, Ahmedabad',
            'status' => 1,
            'image' => null,
        ]);
        
    DB::table('user_addresses')->insert([
            'user_id' => $userId,
            'full_address' => 'Satellite, Ahmedabad',
            'pincode' => '380015',
            'is_selected' => 1,
            'latitude' => 23.0300,
            'longitude' => 72.5100,
        ]);
        
    $chefId = DB::table('chefs')->insertGetId([
            'name' => 'Chef Kunal',
            'email' => 'kuna22@example.com',
            'phone_number' => '9998887777',
            'address' => 'Prahladnagar, Ahmedabad',
            'business_name' => 'Kunal Kitchen',
            'kitchen_type' => 'North Indian',
            'daily_capacity' => 50,
            'pincode' => '380015',
            'city' => 'Ahmedabad',
            'latitude' => 23.0285,
            'longitude' => 72.5245,
            'available' => 1,
            'cover_image' => 'test.jpg',
            'profile_image' => 'test.png'
        ]);
        
    DB::table('food_dishes')->insert([
            [
                'name' => 'Paneer Butter Masala',
                'description' => 'Creamy tomato gravy with paneer',
                'price' => 250,
                'image' => 'paneer.jpg',
                'category_id' => 1,
                'spicy_level' => 'Medium',
                'chef_id' => $chefId,
                'is_active' => 1,
                'ingredients' => 'Paneer, Tomato, Butter, Spices',
                'cuisine_type_id' => 1,
            ],
            [
                'name' => 'Veg Biryani',
                'description' => 'Fragrant basmati rice with spices',
                'price' => 180,
                'image' => 'biryani.jpg',
                'category_id' => 2,
                'spicy_level' => 'High',
                'chef_id' => $chefId,
                'is_active' => 1,
                'ingredients' => 'Rice, Vegetables, Spices',
                'cuisine_type_id' => 2,
            ],
        ]);
});
}

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');



// Specific Export Routes
Route::get('/export-roles', [ExportController::class, 'exportRoles'])->name('export.roles');
Route::get('/export-categories', [ExportController::class, 'exportCategories'])->name('export.categories');

Route::get('/export-tags', [ExportController::class, 'exportTags'])->name('export.tags');

Route::get('/export-cuisine-types', [ExportController::class, 'exportCuisineTypes'])->name('export.cuisine.types');

Route::get('/export-customers', [ExportController::class, 'exportCustomers'])->name('export.customers');

Route::get('/export-chefs', [ExportController::class, 'exportChefs'])->name('export.chefs');

Route::get('/export-orders', [ExportController::class, 'exportOrders'])->name('export.orders');

Route::get('/export-coupons', [ExportController::class, 'exportCoupons'])->name('export.coupons');

Route::get('/export-pages', [ExportController::class, 'exportPages'])->name('export.pages');

Route::get('/export-issues', [ExportController::class, 'exportIssues'])->name('export.issues');


Route::get('/export-active-chefs', [ExportController::class, 'exportActiveChefs'])->name('export.active.chefs');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/change-password', [PasswordController::class, 'showChangePasswordForm'])->name('password.change.form');
    Route::post('/change-password', [PasswordController::class, 'update'])->name('password.change');

    Route::get('roles', [RolesController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [RolesController::class, 'create'])->name('roles.create');
    Route::post('roles/store', [RolesController::class, 'store'])->name('roles.store');
    Route::get('roles/edit/{id}', [RolesController::class, 'edit'])->name('roles.edit');
    Route::post('roles/update/{id}', [RolesController::class, 'update'])->name('roles.update');
    Route::post('roles/delete/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');

    Route::resource('issues', App\Http\Controllers\IssueController::class);
    Route::post('issues/{id}/status', [App\Http\Controllers\Admin\IssueController::class, 'updateStatus'])->name('issues.status');

    Route::get('/categories', [CategoryController::class, 'list'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    

// Allergy Routes
Route::prefix('allergies')->name('allergies.')->group(function () {
    Route::get('/', [AllergyController::class, 'list'])->name('index');
    Route::get('/create', [AllergyController::class, 'create'])->name('create');
    Route::post('/store', [AllergyController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [AllergyController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [AllergyController::class, 'update'])->name('update');
    Route::delete('/destroy/{id}', [AllergyController::class, 'destroy'])->name('destroy');
});
    
    
     Route::get('/tags', [TagController::class, 'list'])->name('tags.index');
    Route::get('/tags/create', [TagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{id}/edit', [TagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{id}', [TagController::class, 'update'])->name('tags.update');
    Route::post('/tags/{id}', [TagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/payouts/create', [PayoutController::class, 'create'])
        ->name('admin.payouts.create');
        
        Route::post('/admin/payouts/mark-paid/{chef_id}', [PayoutController::class, 'markPaid'])->name('admin.payouts.mark-paid');

    Route::post('/payouts/run', [PayoutController::class, 'run'])
        ->name('admin.payouts.run');

    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('admin.payouts.index');
        
        Route::get('/notifications/create', [AdminNotificationController::class, 'create'])->name('admin.notifications.create');
    Route::post('/notifications/send', [AdminNotificationController::class, 'send'])->name('admin.notifications.send');
    Route::get('/notifications/history', [AdminNotificationController::class, 'history'])->name('admin.notifications.history');

    Route::post('/payouts/{id}/retry', [PayoutController::class, 'retry'])
        ->name('admin.payouts.retry');

    Route::post('/payouts/chef/{chefId}', [PayoutController::class, 'chefPayoutSingle'])
        ->name('admin.payouts.chef-single');

    // Route::get('/payouts/create', [PayoutController::class, 'create'])
    //     ->name('admin.payouts.create');

    // Route::post('/payouts/run', [PayoutController::class, 'run'])
    //     ->name('admin.payouts.run');

    // Route::get('/payouts', [PayoutController::class, 'index'])
    //     ->name('admin.payouts.index');

    Route::get('/cuisine-types', [CuisineTypeController::class, 'index'])->name('cuisine-types.index');
    Route::get('/cuisine-types/create', [CuisineTypeController::class, 'create'])->name('cuisine-types.create');
    Route::post('/cuisine-types', [CuisineTypeController::class, 'store'])->name('cuisine-types.store');
    Route::get('/cuisine-types/{id}/edit', [CuisineTypeController::class, 'edit'])->name('cuisine-types.edit');
    Route::post('/cuisine-types/{id}', [CuisineTypeController::class, 'update'])->name('cuisine-types.update');
    Route::post('/cuisine-types/{id}/delete', [CuisineTypeController::class, 'destroy'])->name('cuisine-types.destroy');

    Route::resource('food-items', FoodItemController::class);

    Route::resource('users', UsersController::class);

    Route::get('/customers', [CustomerManagementController::class, 'index'])->name('customers.index');
    Route::get('/customers/{id}', [CustomerManagementController::class, 'show'])->name('customers.show');
    Route::delete('/customers/{id}', [CustomerManagementController::class, 'destroy'])->name('customers.destroy');

    
    Route::resource('coupons', CouponController::class);
    Route::get('/get-dishes/{chef_id}', [CouponController::class, 'getDishes'])->name('get.dishes');


    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/create-setting', [SettingController::class, 'create'])->name('setting.create');
    Route::post('/save-setting', [SettingController::class, 'save'])->name('save.setting');
    Route::get('/edit-setting/{id}', [SettingController::class, 'edit'])->name('edit.setting');
    Route::get('/delete-setting/{id}', [SettingController::class, 'delete'])->name('delete.setting');
    Route::post('/update-setting', [SettingController::class, 'update'])->name('update.setting');


   



    Route::get('/smtp-settings', [SmtpSettingController::class, 'list'])->name('smtp.index');
    Route::get('/smtp-settings/create', [SmtpSettingController::class, 'create'])->name('smtp.create');
    Route::post('/smtp-settings', [SmtpSettingController::class, 'store'])->name('smtp.store');
    Route::get('/smtp-settings/{id}/edit', [SmtpSettingController::class, 'edit'])->name('smtp.edit');
    Route::put('/smtp-settings/{id}', [SmtpSettingController::class, 'update'])->name('smtp.update');
    Route::delete('/smtp-settings/{id}', [SmtpSettingController::class, 'destroy'])->name('smtp.destroy');
    Route::get('/smtp-settings/{id}/test', [SmtpSettingController::class, 'test'])->name('smtp.test');


    Route::post('/razorpay/webhook', [OrderManagementController::class, 'handle']);
    Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderManagementController::class, 'show'])->name('orders.show');
    Route::post('/orders/refund', [OrderManagementController::class, 'refund'])
    ->name('orders.refund');
    Route::post('/orders/update-status-management', [OrderManagementController::class, 'updateStatusOrderManagement'])
        ->name('orders.updateStatusManagement');

    // Route::get('/global-search-api', [SearchController::class, 'apiSearch']);

    Route::resource('preferences', PreferencesController::class);
    Route::resource('mealtimes', MealTimesController::class);
    // Route::resource('foods', FoodController::class);

    Route::get('/radius-setting', [RadiusSettingController::class, 'index'])->name('radius.index');
    Route::get('/radius-setting/create', [RadiusSettingController::class, 'create'])->name('radius.create');
    Route::post('/radius-setting/store', [RadiusSettingController::class, 'store'])->name('radius.store');
    Route::get('/radius-setting/edit/{id}', [RadiusSettingController::class, 'edit'])->name('radius.edit');
    Route::post('/radius-setting/update/{id}', [RadiusSettingController::class, 'update'])->name('radius.update');
    Route::delete('/radius-setting/delete/{id}', [RadiusSettingController::class, 'destroy'])->name('radius.destroy');

    // Route::resource('food_dishes', FoodDishController::class);


    Route::get('/restaurants', [RestaurantTypeController::class, 'list'])->name('restaurants.index');
    Route::get('/restaurants/create', [RestaurantTypeController::class, 'create'])->name('restaurants.create');
    Route::post('/restaurants', [RestaurantTypeController::class, 'store'])->name('restaurants.store');
    Route::get('/restaurants/{id}/edit', [RestaurantTypeController::class, 'edit'])->name('restaurants.edit');
    Route::put('/restaurants/{id}', [RestaurantTypeController::class, 'update'])->name('restaurants.update');
    Route::post('/restaurants/{id}', [RestaurantTypeController::class, 'destroy'])->name('restaurants.destroy');

    // Route::get('chefs/', [ChefController::class, 'index'])->name('index');
    Route::get('/chefs', [ChefManagementController::class, 'index'])->name('chefs.index');
    Route::post('/chefs/verify-toggle', [ChefManagementController::class, 'toggleVerify'])
    ->name('chefs.verify.toggle');
    Route::get('/chef/create', [ChefManagementController::class, 'create'])->name('chefs.create');
    Route::post('/chefs', [ChefManagementController::class, 'store'])->name('chefs.store');
    Route::get('/chefs/{chef}/edit', [ChefManagementController::class, 'edit'])->name('chefs.edit');
    Route::put('/chefs/{chef}', [ChefManagementController::class, 'update'])->name('chefs.update');
    Route::delete('/chefs/{id}', [ChefManagementController::class, 'destroy'])->name('chefs.destroy');
    Route::get('/chefs-details/{id}', [ChefManagementController::class, 'show'])->name('chefs.details');
    Route::post('/chefs/{id}/download-personal-docs', [ChefManagementController::class, 'downloadPersonalDocs'])
    ->name('chefs.downloadPersonalDocs');
    Route::post('/chefs/{id}/download-fssai', [ChefManagementController::class, 'downloadFssaiDocs'])->name('chefs.downloadFssaiDocs');
    Route::post('/chefs/{id}/download-self', [ChefManagementController::class, 'downloadSelfDocs'])->name('chefs.downloadSelfDocs');
    Route::post('/chefs/{id}/update-document-status', [ChefManagementController::class, 'updateDocumentStatus'])->name('chefs.updateDocumentStatus');


    Route::post('/chefs/toggle', [ChefManagementController::class, 'toggleIsOpned'])->name('chefs.toggle');
    Route::post('/orders/update-status', [ChefManagementController::class, 'updateStatusOrder'])
        ->name('orders.updateStatus');
    // routes/web.php
    Route::post('/chefs/reviews-toggle-approval/{id}', [ChefManagementController::class, 'toggleApproval'])->name('chefs.reviews.toggle-approval');

    Route::get('/chef/download/{filename}', [ChefManagementController::class, 'downloadDocument'])->name('chef.download');


    Route::get('/food_dishes/create', [FoodDishController::class, 'create'])->name('food_dishes.create');
    Route::post('/food_dishes', [FoodDishController::class, 'store'])->name('food_dishes.store');
    Route::get('/food_dishes/edit/{foodDish}', [FoodDishController::class, 'edit'])->name('food_dishes.edit');
    Route::put('/food_dishes/{foodDish}', [FoodDishController::class, 'update'])->name('food_dishes.update');
    Route::delete('/food_dishes/{foodDish}', [FoodDishController::class, 'destroy'])->name('food_dishes.destroy');
    Route::post('/food-dishes/toggle-status/{id}', [FoodDishController::class, 'toggleActive'])->name('food_dishes.toggleActive');
    Route::post('/food-dishes/toggle-recommended/{id}', [FoodDishController::class, 'toggleRecommended'])
    ->name('food_dishes.toggleRecommended');
    
    
    Route::get('/continuous_audits/create', [ContinuousAuditsController::class, 'create'])->name('continuous_audits.create');
    Route::post('/continuous_audits', [ContinuousAuditsController::class, 'store'])->name('continuous_audits.store');
    Route::get('/continuous_audits/edit/{continuousAudits}', [ContinuousAuditsController::class, 'edit'])->name('continuous_audits.edit');
    Route::put('/continuous_audits/{continuousAudits}', [ContinuousAuditsController::class, 'update'])->name('continuous_audits.update');
    Route::delete('/continuous_audits/{continuousAudits}', [ContinuousAuditsController::class, 'destroy'])->name('continuous_audits.destroy');
        
        
        
    // In web.php
Route::put('/chefs/{chef}/upload-kitchen-photos', [ChefController::class, 'uploadKitchenPhotos'])
    ->name('chefs.uploadKitchenPhotos');
    Route::get('/kitchen_photos/create', [KitchenPhotosController::class, 'create'])->name('kitchen_photos.create');
    Route::post('/kitchen_photos', [KitchenPhotosController::class, 'store'])->name('kitchen_photos.store');
    Route::get('/kitchen_photos/edit/{kitchenPhotos}', [KitchenPhotosController::class, 'edit'])->name('kitchen_photos.edit');
    Route::put('/kitchen_photos/{kitchenPhotos}', [KitchenPhotosController::class, 'update'])->name('kitchen_photos.update');
    Route::delete('/kitchen_photos/{kitchenPhotos}', [KitchenPhotosController::class, 'destroy'])->name('kitchen_photos.destroy');
    Route::post('/kitchen_photos/{kitchenPhotos}/status', [KitchenPhotosController::class, 'updateStatus'])
    ->name('kitchen_photos.updateStatus');
    Route::post('/kitchen-photo/delete-image', [KitchenPhotosController::class,'deleteImage'])
    ->name('kitchen.photo.delete');

    

    Route::resource('pages', PagesController::class);
    
    Route::resource('homescreen', HomeScreenController::class);
    
    Route::get('/export/{model}', [PDFExportController::class, 'export'])->name('pdf.export');

    // Route::get('/export/{model}/{chef_id}', [PDFExportController::class, 'export'])->name('pdf.exportByChef');


});

require __DIR__ . '/auth.php';
