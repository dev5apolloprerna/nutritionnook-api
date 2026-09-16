<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChefController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ChefAddressAndServingAreaController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\ChefManagementController;
use App\Http\Controllers\Api\ChefOnlineStatusController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\FoodItemsController;
use App\Http\Controllers\Api\NearByChefsController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\UserAddressController;
use App\Models\ChefOnlineStatus;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\InvoiceController;

use App\Http\Controllers\Api\CouponController;




Route::get('/list-razorpay-gateway', [ApiController::class,'listRazorpayGateway']);
Route::get('/test-fcm', function () {
   
    $SERVER_KEY = env('FIREBASE_SERVER_KEY');

    $token = "eXxg7hBOS8OQUZVt-s0GDL:APA91bFLDDZp4CroLLCd8o5K-kp6BZGeQHup_JQb2rTVwI-HWHsXpQT6kHfOgSOteplpSuPlICgNXasUXT6cLGMZkJ8bIQqlXbSh0bcvyPAAIPOddogvx1Y"; // device token from React Native dev

    $response = Http::withHeaders([
        'Authorization' => 'key=' . $SERVER_KEY,
        'Content-Type' => 'application/json',
    ])->post('https://fcm.googleapis.com/fcm/send', [
        'to' => $token,
        'notification' => [
            'title' => 'Test from Laravel Token',
            'body' => 'If you see this, FCM is working!',
        ],
    ]);

    return $response->json();
});


Route::get('/update-buffer-status-by-order-id/{order_id}', [ApiController::class,'updateBufferStatusByOrderId']);

Route::get('/settings', [MasterController::class, 'getSettings']);
Route::get('settings/contact-details', [ApiController::class, 'getSettings']);

Route::get('/update-buffer-status/{order_id}', [ApiController::class,'updateBufferStatus']);
Route::get('/list-chef-cuisine-type', [MasterController::class, 'listCuisinChefType']);
Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return response()->json(['message' => 'Cache cleared successfully!']);
});

Route::get('/terms-and-conditions', [ApiController::class, 'termsAndConditions']);
Route::get('/privacy-policy', [ApiController::class, 'privacyPolicy']);
Route::get('/about-us', [ApiController::class, 'aboutUs']);

Route::post('signup', [AuthController::class, 'register']);

Route::post('signin', [AuthController::class, 'signin']);

// Route::post('/send-otp', [AuthController::class, 'sendOtp']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);


Route::get('/list-allergies', [ApiController::class, 'getAllergies']);


Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

// Route::post('/add-role', [MasterController::class, 'addRole']);
// Route::get('/list-role', [MasterController::class, 'listRole']);
// Route::post('/edit-role', [MasterController::class, 'editRole']);
// Route::post('/delete-role', [MasterController::class, 'deleteRole']);

// Route::post('/add-category', [MasterController::class, 'addCategory']);
Route::get('/list-category', [MasterController::class, 'listCategory']);
// Route::post('/edit-category', [MasterController::class, 'editCategory']);
// Route::post('/delete-category', [MasterController::class, 'deleteCategory']);

// Route::post('/add-cuisine-type', [MasterController::class, 'addCuisineType']);

// Route::post('/edit-cuisine-type', [MasterController::class, 'editCuisineType']);
// Route::post('/delete-cuisine-type', [MasterController::class, 'deleteCuisineType']);

Route::get('/list-tags', [MasterController::class, 'listTags']);

Route::get('/available-dates', [MasterController::class, 'getAvailableDates']);

Route::get('/cron/send-pre-order-reminders', [ApiController::class, 'sendPreOrderReminders']);

Route::get('chefs/by-tag', [ChefController::class, 'getChefByTag']);

// Route::get('/chef/{id}', [ChefController::class, 'getChefById']);

Route::get('/chef/{id}/top-picks', [ChefController::class, 'getTopPicks']);

    //  Route::get('get-now-dishes', [FoodItemsController::class, 'getTodayDishes']);
     Route::get('/list-homescreen-details', [ApiController::class,'getHomeScreens']);
    

Route::get('/app-version', [ApiController::class, 'appVersion']);

    Route::get('/chefs/{chef_id}/reviews', [ReviewController::class, 'getChefReviews']);


     Route::get('/nearby-chefs-test/{id}', [NearByChefsController::class, 'getNearbyChefstest']);
     
     Route::post('/chef-register', [ApiController::class, 'chefsRegister']);
     
         Route::get('/get-radius', [ApiController::class, 'getradius']);
         
         // for Guest User Code start here
         Route::get('/search', [SearchController::class, 'search']);
         Route::get('/list-various-food-dishes', [FoodItemsController::class, 'listVariousFoodDishes']);

         Route::get('today-resturants', [ChefController::class,'todayChef']);
    Route::get('pre-order-resturants', [ChefController::class,'preOrderRestaurants']);
    Route::get('/gst', [ApiController::class, 'getGst']);
    
     Route::get('get-now-dishes/{chef_id}', [FoodItemsController::class, 'getTodayDishes']);
Route::get('get-later-dishes/{chef_id}', [FoodItemsController::class, 'getLaterDishes']);
Route::get('/chef/{id}', [ChefController::class, 'getChefById'])
    ->whereNumber('id');


          Route::get('/list-cuisine-type', [MasterController::class, 'listChefType']);
          Route::get('/nearby-chefs', [NearByChefsController::class, 'getNearbyChefs']);
          Route::get('latest-southindian-northindian-chefs/{cuisineTypeId}', [FoodItemsController::class, 'getLatestChefByCuisine']);
Route::get('all-southindian-northindian-chefs/{cuisineTypeId}', [FoodItemsController::class, 'getAllChefByCuisine']);
Route::get('latest-food-by-tag/{tagId}', [FoodItemsController::class, 'getLatestFoodByTag']);
Route::get('all-food-by-tag/{tagId}', [FoodItemsController::class, 'getAllFoodByTag']);
Route::get('/get-selected-address', [UserAddressController::class, 'getSelectedAddress']);

Route::get('/get-selected-address-with-chef/{chef_id}', [UserAddressController::class, 'getSelectedAddressWithChef']);

Route::post('/webhooks/razorpay/payout', [RazorpayWebhookController::class, 'handlePayout']);
Route::post('/webhooks/razorpay/refund', [RazorpayWebhookController::class, 'handleRefund']);
        //  for Guest User Code end here
         
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chef/update-profile', [ApiController::class, 'updateProfile']);
    Route::get('/get-order-preparation-time/{order_id}', [ApiController::class, 'getPreparationTime']);
    Route::delete('/chef/delete-account', [ApiController::class, 'deleteAccount']);
    Route::post('save-fcm-token', [NotificationController::class, 'saveFcmToken']);
    Route::post('notifications/send', [NotificationController::class, 'sendNotification']);

    Route::post('chef/orders/{orderId}/status', [ApiController::class, 'updateOrderStatus']);
    Route::get('/list-food-dishes', [ApiController::class,'listFoodDishes']);
    Route::get('/search-food-dishes', [ApiController::class,'searchFoodDishes']);
    Route::post('/create-order', [ApiController::class, 'createOrder']);
    Route::post('/create-order-one', [OrderController::class, 'store']);
    Route::POST ('/verify-payment', [ApiController::class,'verifyPayment']);
    // Route::get('/gst', [ApiController::class, 'getGst']);
    
   
    
    Route::get('/payment-list', [ApiController::class, 'paymentList']);
    Route::post('/payment-details/{order_id}', [ApiController::class, 'paymentDetails']);
    Route::get('/payment-list-pdf',[ApiController::class, 'generatePaymentPdf']);
    Route::get('/chef/is-verified', [ApiController::class, 'checkVerificationStatus']);


    Route::get('chef/payouts', [ApiController::class, 'getPayoutTransactions']);
    Route::get('chef/payouts/{id}', [ApiController::class, 'getPayoutDetail']);

    Route::get('/orders/{id}/accept', [ApiController::class, 'acceptOrder']);
    Route::post('/orders/{id}/reject', [ApiController::class, 'rejectOrder']);
    Route::get('/chef-orders', [ApiController::class, 'listChefOrders']);
    
    Route::delete('/chef/delete-account', [ApiController::class, 'chefDeleteAccount']);
    Route::delete('/customer/delete-account', [ApiController::class, 'customerDeleteAccount']);
    
    Route::get('chef/earnings-overview',[ApiController::class, 'overviewDetails']);

    Route::post('/generate-invoice', [ApiController::class, 'generateInvoice']);
    Route::post('/order-history',[InvoiceController::class, 'generateFilteredInvoice']);
    Route::get('/chef/earnings/overview-pdf',[ApiController::class, 'downloadEarningOverviewPdf']);
     
    // Route::get('today-resturants', [ChefController::class,'todayChef']);
    // Route::get('pre-order-resturants', [ChefController::class,'preOrderRestaurants']);
    
    Route::get('chef/analytics', [ChefController::class,'analytics']);
    
    
    Route::patch('chef/status',[ChefOnlineStatusController::class,'updateOpenStatus']);
    Route::get('chef-category',[ChefOnlineStatusController::class,'getChefCategory']);
    Route::get('/order-detail/{id}',[ChefOnlineStatusController::class,'getOrderDetail']);
    Route::get('get-chef-details',[ChefOnlineStatusController::class,'getChefDetail']);

    Route::post('/add-food-items', [FoodItemsController::class, 'addFoodItems']);
    Route::get('/list-food-items', [FoodItemsController::class, 'listFoodItems']);
    Route::post('/edit-food-items', [FoodItemsController::class, 'editFoodItems']);
    Route::post('/delete-food-items', [FoodItemsController::class, 'deleteFoodItems']);
    Route::get('chef-dishes', [FoodItemsController::class, 'searchFoodItems']);
    Route::post('chef/dishes/{dish_id}/stock', [FoodItemsController::class, 'updateStock']);
    Route::post('chef/dishes/{dish_id}/recommendation', [FoodItemsController::class, 'updateRecommendation']);
    
    
    
    Route::get('/list-coupon-code', [MasterController::class, 'listCouponCode']);

    Route::get('/loggeduser', [AuthController::class, 'loggeduser']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/list-menu-items', [MenuItemController::class, 'index']);

    Route::get('/list-banners', [BannerController::class, 'index']);

    Route::get('/list-restaurants', [RestaurantController::class, 'index']);
    Route::get('/list-restaurants-details/{id}', [RestaurantController::class, 'show']);

    Route::post('/add-users-address', [UserAddressController::class, 'store']);
    Route::get('/list-users-address', [UserAddressController::class, 'listUserAddresses']);
    Route::post('/edit-users-address', [UserAddressController::class, 'update']);
    Route::post('/delete-users-address', [UserAddressController::class, 'delete']);
    
    

    // Save/Create issue API
    Route::post('/create-issue', [IssueController::class, 'saveIssue']);
    
    // Get issues for authenticated user
    Route::get('/user/issues', [IssueController::class, 'getUserIssues']);
    
    // Get issues for specific user by ID
    Route::get('/user/{issueId}/issues', [IssueController::class, 'getUserIssues']);
    
    // Get single issue details
    Route::get('/issues/{issueId}', [IssueController::class, 'getIssueDetails']);
    
    Route::post('/issues/{issueId}', [IssueController::class, 'updateIssue']);
    
     Route::delete('/issues/{issueId}', [IssueController::class, 'deleteIssue']);
    
    // Update issue status
    Route::put('/issues/{issueId}/status', [IssueController::class, 'updateIssueStatus']);

    Route::post('/user-select-address', [UserAddressController::class, 'selectAddress']);
    
    

    Route::post('add-chef-main-address', [ChefAddressAndServingAreaController::class, 'storeMainAddress']);
    Route::get('list-chef-main-address', [ChefAddressAndServingAreaController::class, 'listChefAddresses']);
    Route::post('edit-chef-main-address', [ChefAddressAndServingAreaController::class, 'updateChefAddress']);
    Route::post('delete-chef-main-address', [ChefAddressAndServingAreaController::class, 'deleteChefAddress']);
    
    Route::post('add-chef-serving-areas', [ChefAddressAndServingAreaController::class, 'storeServingAreas']);
    Route::get('list-chef-serving-areas', [ChefAddressAndServingAreaController::class, 'listServingAreas']);
    Route::post('edit-chef-serving-areas', [ChefAddressAndServingAreaController::class, 'updateServingArea']);
    Route::post('delete-chef-serving-areas', [ChefAddressAndServingAreaController::class, 'deleteServingArea']);

   
    Route::get('/popular-dishes', [NearByChefsController::class, 'getPopularDishes']);

    Route::post('/chef-open-status', [ChefOnlineStatusController::class, 'updateOpenStatus']);
    
    // Route::get('/search', [SearchController::class, 'search']);
    
    Route::get('/my_orders', [OrderController::class, 'myOrders']);
    
    Route::get('past-orders', [OrderController::class, 'pastOrders']);
    
    // Route::get('/my_orders_search', [OrderController::class, 'myOrdersSearch']);
    
    Route::get('/orders-details/{id}', [OrderController::class, 'orderDetail']);
    
    
    Route::post('/update-customer', [ApiController::class, 'updateCustomer']);
    
    Route::post('/add-review', [ReviewController::class, 'addReview']);
    
    // Route::get('/get-chef-reviews', [ReviewController::class, 'getChefReviews']);

    
    Route::post('/add-rating', [RatingController::class, 'addRating']);

Route::get('/orders/{id}/invoice', [InvoiceController::class, 'downloadInvoice']);


 Route::post('/favourite-chefs', [ChefController::class, 'storeFavChef']);   // Add
    Route::post('/favourite-chefs/{chefId}', [ChefController::class, 'destroyFavChef']); // Remove
    







Route::get('list-coupon', [CouponController::class, 'listCoupons']);



Route::post('/apply-coupon', [CouponController::class, 'applyCoupon']);





});
