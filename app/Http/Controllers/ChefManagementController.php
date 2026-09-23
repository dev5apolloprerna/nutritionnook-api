<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use App\Models\User;
use App\Helpers\CommonHelper;
use App\Services\ChefPayoutService;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Review;
use App\Models\FoodDish;
use App\Models\FoodItem;
use Illuminate\Http\Request;
use Mail;
use App\Services\FCMService;
use App\Models\RestaurantType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ContinuousAudits;
use App\Models\KitchenPhotos;
use ZipArchive;
use Illuminate\Support\Facades\Storage;


class ChefManagementController extends Controller
{
    protected ChefPayoutService $chefPayoutService;

    public function __construct(ChefPayoutService $chefPayoutService)
    {
        $this->chefPayoutService = $chefPayoutService;
    }
 
public function toggleVerify(Request $request)
{
    \Log::info('toggleVerify API called', [
        'request' => $request->all()
    ]);

    $chef = Chef::find($request->id);

    if (!$chef) {
        \Log::error('Chef not found', ['chef_id' => $request->id]);

        return response()->json([
            'success' => false,
            'message' => 'Chef not found'
        ]);
    }

    // Update verify status
    $chef->is_verify = $request->is_verify;
    $chef->save();

    \Log::info('Chef verification status updated', [
        'chef_id' => $chef->id,
        'is_verify' => $chef->is_verify
    ]);

    // 🔔 Send push notification ONLY when verified
    if ((int) $request->is_verify == 1) {

        \Log::info('Entering notification block', [
            'chef_id' => $chef->id,
            'user_id' => $chef->user_id
        ]);

        $setting = DB::table('settings')->first();

        \Log::info('Settings fetched', [
            'settings' => $setting
        ]);

        $logo = ($setting && $setting->logo)
            ? url('public/images/' . $setting->logo)
            : '';

        $title = 'Verification Completed 🎉';
        $body  = 'Congratulations! Your chef profile has been verified. You can now start receiving orders. 🚀';

        $data = [
            'title' => $title,
            'body'  => $body,
            'type'  => 'chef_verification',
            'chefId' => (string) $chef->id,
            'logo'   => $logo,
        ];

        \Log::info('Notification data prepared', [
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        try {
            $fcmService = new FCMService();

            \Log::info('Calling FCMService', [
                'user_id' => $chef->id
            ]);

            $fcmService->sendNotificationToUser($chef->id, $title, $body, $data);

            \Log::info('Push notification sent successfully');

            // 📧 Send email to chef
            $user = DB::table('users')->where('id', $chef->user_id)->first();

            \Log::info('User fetched for email', [
                'user' => $user
            ]);

            if ($user && !empty($user->email)) {

                \Log::info('Sending email to chef', [
                    'email' => $user->email
                ]);

                Mail::send('emails.chef-verification', [
                    'name' => $user->name ?? 'Chef'
                ], function ($message) use ($user) {

                    $message->to($user->email)
                        ->subject('Chef Profile Verified 🎉');

                });

                \Log::info('Email sent successfully');
            }

        } catch (\Throwable $e) {
            \Log::error('Chef verification push failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => $request->is_verify
            ? 'Chef verified successfully'
            : 'Chef unverified successfully'
    ]);
}

    public function index()
    {
        
        $chefs = \DB::table('chefs')->get();
       
        return view('admin.chefs.list', compact('chefs'));
    }
    public function create()
    {
        // $restaurantTypes = RestaurantType::all();
        return view('admin.chefs.form', [ 'chef' => null ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:chefs,email',
        'phone_number' => 'required|numeric',
        'address' => 'required|string',
        'pincode' => 'required|digits:6',
        'dob' => 'required',
        'gender' => 'required|in:male,female,other',
        'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif',
        'cover_image'   => 'required|image|mimes:jpeg,png,jpg,gif',
        'kitchen_name' => 'required|string|max:255',
        'account_holder_name' => 'nullable|string',
        'fssai_license_number' => 'required|string|size:14',
        'fssai_validity_date' => 'required|date',
        'commission' => 'required|numeric',
        'opening_time' => 'required|date_format:H:i',
        'closing_time' => 'required|date_format:H:i',
    
        'bank_name' => 'required|string',
        'account_number' => 'required|digits_between:9,18',
        'ifsc_code' =>  ['required', 'size:11', 'regex:/^[A-Z]{4}0[0-9A-Z]{6}$/'],
        'pan_card' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],

        'personal_document_type' => 'nullable|string|in:aadhar_card,pan_card,driving_license',
        'personal_documents' => 'nullable|array',
        'personal_documents.*' => 'mimes:jpeg,png,jpg,pdf',
        
        'fscai_certificate' => 'nullable|array',
        'fscai_certificate.*' => 'mimes:jpeg,png,jpg,pdf',

        'self_declaration' => 'nullable|array',
        'self_declaration.*' => 'mimes:jpeg,png,jpg,pdf',
        
        'cuisine_speciality' => 'required|string|max:255',
        'preference_tags' => 'nullable|array',
        'kitchen_assessment_photographs' => 'nullable|array',
        'kitchen_assessment_photographs.*' => 'image|mimes:jpeg,png,jpg,gif',
        
        'chef_training' => 'nullable|date',
        'onboarding_kit_receipt' => 'nullable|date',
        
        'shop_plot_number' => 'nullable|string',
        'floor' => 'nullable|string',
        'building_name' => 'nullable|string',
        'city' => 'nullable|string',
        
        'about_chef' => 'nullable|string',
        'working_days' => 'nullable|array',
        'delivery_radius' => 'nullable|string'
    ]);
    
    $data = $request->except(['profile_image','cover_image']);
    
    // // Convert array fields to JSON
    // $data['working_days'] = !empty($request->working_days) ? json_encode($request->working_days) : json_encode([]);
    // Chef casts working_days to JSON; passing an encoded string here would
    // encode it a second time and break JSON membership queries.
    $data['working_days'] = $request->input('working_days', []);
    $data['preference_tags'] = !empty($request->preference_tags) ? json_encode($request->preference_tags) : json_encode([]);
    
    // Handle profile image
    if ($request->hasFile('profile_image')) {
        $file = $request->file('profile_image');
        $fileName = time().'_profile_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images'), $fileName);
        $data['profile_image'] = 'public/images/' . $fileName;
    }
    
    // Handle cover image
    if ($request->hasFile('cover_image')) {
        $file = $request->file('cover_image');
        $fileName = time().'_cover_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images'), $fileName);
        $data['cover_image'] = 'public/images/' . $fileName;
    }

    // Handle kitchen photos
    if ($request->hasFile('kitchen_assessment_photographs')) {
        $kitchenPhotos = [];
        foreach ($request->file('kitchen_assessment_photographs') as $file) {
            $fileName = time().'_kitchen_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/kitchen_photos'), $fileName);
            $kitchenPhotos[] = 'public/images/kitchen_photos/' . $fileName;
        }
        $data['kitchen_assessment_photographs'] = json_encode($kitchenPhotos);
    }

    // Handle FSSAI certificates
    if ($request->hasFile('fscai_certificate')) {
        $fscai_certificate = [];
        foreach ($request->file('fscai_certificate') as $file) {
            $fileName = time().'_fscai_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $fscai_certificate[] = 'public/documents/' . $fileName;
        }
        $data['fscai_certificate'] = json_encode($fscai_certificate);
    }

    // Handle personal documents
    if ($request->hasFile('personal_documents')) {
        $personal_documents = [];
        foreach ($request->file('personal_documents') as $file) {
            $fileName = time().'_personal_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $personal_documents[] = 'public/documents/' . $fileName;
        }
        $data['personal_documents'] = json_encode($personal_documents);
    }

    // Handle self declaration
    if ($request->hasFile('self_declaration')) {
        $self_declaration = [];
        foreach ($request->file('self_declaration') as $file) {
            $fileName = time().'_self_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $self_declaration[] = 'public/documents/' . $fileName;
        }
        $data['self_declaration'] = json_encode($self_declaration);
    }
    
    if ($request->filled('opening_time')) {
        $data['opening_time'] = Carbon::createFromFormat('H:i', $request->opening_time)->format('h:i A');
    }
    
    if ($request->filled('closing_time')) {
        $data['closing_time'] = Carbon::createFromFormat('H:i', $request->closing_time)->format('h:i A');
    }
    
    $chef = Chef::create($data);
    
    $bankDetails = [
        'account_holder_name' => $chef->name,
        'account_number'      => $request->account_number,
        'ifsc'                => $request->ifsc_code,
    ];

    // $razorpayResult = $this->chefPayoutService->registerChefBankDetails($chef, $bankDetails);
    return redirect()->route('chefs.index')->with('success', 'Chef added successfully.');
}
    public function edit(Chef $chef)
    {
    
        return view('admin.chefs.form', compact('chef'));
    }


 public function update(Request $request, Chef $chef)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:chefs,email,' . $chef->id,
        'phone_number' => 'required|numeric',
        'address' => 'required|string',
        'pincode' => 'required|digits:6',
        'dob' => 'required',
        'gender' => 'required|in:male,female,other',

        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        'cover_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif',
        
        'kitchen_name' => 'required|string|max:255',
        // 'kitchen_type' => 'required|string',
        'fssai_license_number' => 'required|string|size:14',
        'fssai_validity_date' => 'required|date',
        'account_holder_name' => 'nullable|string',
        'commission' => 'required|numeric',

        'opening_time' => 'required|date_format:H:i',
        'closing_time' => 'required|date_format:H:i',

        'bank_name' => 'required|string',
        'account_number' => 'required|digits_between:9,18',
        'pan_card' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
        'ifsc_code' =>  ['required', 'size:11', 'regex:/^[A-Z]{4}0[0-9A-Z]{6}$/'],

        'personal_document_type' => 'nullable|string|in:aadhar_card,pan_card,driving_license',
        'personal_documents' => 'nullable|array',
        'personal_documents.*' => 'mimes:jpeg,png,jpg,pdf',
        
        'fscai_certificate' => 'nullable|array',
        'fscai_certificate.*' => 'mimes:jpeg,png,jpg,pdf',

        'self_declaration' => 'nullable|array',
        'self_declaration.*' => 'mimes:jpeg,png,jpg,pdf',
        
        'cuisine_speciality' => 'required|string|max:255',
        'preference_tags' => 'nullable|array',
        'kitchen_assessment_photographs' => 'nullable|array',
        'kitchen_assessment_photographs.*' => 'image|mimes:jpeg,png,jpg,gif',
        
        'chef_training' => 'nullable|date',
        'onboarding_kit_receipt' => 'nullable|date',
        
        'shop_plot_number' => 'nullable|string',
        'floor' => 'nullable|string',
        'building_name' => 'nullable|string',
        'city' => 'nullable|string',
        
        'about_chef' => 'nullable|string',
        'working_days' => 'nullable|array',
        'delivery_radius' => 'nullable|string'
    ]);

    $data = $request->except(['profile_image','cover_image']);
    
    // // Convert array fields to JSON
    // $data['working_days'] = !empty($request->working_days) ? json_encode($request->working_days) : json_encode([]);
    // Let the Chef model's array cast encode working_days exactly once.
    $data['working_days'] = $request->input('working_days', []);
    $data['preference_tags'] = !empty($request->preference_tags) ? json_encode($request->preference_tags) : json_encode([]);
    
    // Handle profile image
    if ($request->hasFile('profile_image')) {
        if ($chef->profile_image && file_exists(public_path(str_replace('public/', '', $chef->profile_image)))) {
            unlink(public_path(str_replace('public/', '', $chef->profile_image)));
        }
        $file = $request->file('profile_image');
        $fileName = time().'_profile_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images'), $fileName);
        $data['profile_image'] = 'public/images/' . $fileName;
    }

    // Handle cover image
    if ($request->hasFile('cover_image')) {
        if ($chef->cover_image && file_exists(public_path(str_replace('public/', '', $chef->cover_image)))) {
            unlink(public_path(str_replace('public/', '', $chef->cover_image)));
        }
        $file = $request->file('cover_image');
        $fileName = time().'_cover_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images'), $fileName);
        $data['cover_image'] = 'public/images/' . $fileName;
    }

    // Handle kitchen photos
    if ($request->hasFile('kitchen_assessment_photographs')) {
        $existingPhotos = json_decode($chef->kitchen_assessment_photographs ?? '[]', true);
        foreach ($request->file('kitchen_assessment_photographs') as $file) {
            $fileName = time().'_kitchen_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/kitchen_photos'), $fileName);
            $existingPhotos[] = 'public/images/kitchen_photos/' . $fileName;
        }
        $data['kitchen_assessment_photographs'] = json_encode($existingPhotos);
    }

    // Handle FSSAI certificates
    if ($request->hasFile('fscai_certificate')) {
        $existingCerts = json_decode($chef->fscai_certificate ?? '[]', true);
        foreach ($request->file('fscai_certificate') as $file) {
            $fileName = time().'_fscai_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $existingCerts[] = 'public/documents/' . $fileName;
        }
        $data['fscai_certificate'] = json_encode($existingCerts);
    }

    // Handle personal documents
    if ($request->hasFile('personal_documents')) {
        $existingPersonal = json_decode($chef->personal_documents ?? '[]', true);
        foreach ($request->file('personal_documents') as $file) {
            $fileName = time().'_personal_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $existingPersonal[] = 'public/documents/' . $fileName;
        }
        $data['personal_documents'] = json_encode($existingPersonal);
    }

    // Handle self declaration
    if ($request->hasFile('self_declaration')) {
        $existingSelf = json_decode($chef->self_declaration ?? '[]', true);
        foreach ($request->file('self_declaration') as $file) {
            $fileName = time().'_self_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $existingSelf[] = 'public/documents/' . $fileName;
        }
        $data['self_declaration'] = json_encode($existingSelf);
    }
    
    if ($request->filled('opening_time')) {
        $data['opening_time'] = Carbon::createFromFormat('H:i', $request->opening_time)->format('h:i A');
    }
    
    if ($request->filled('closing_time')) {
        $data['closing_time'] = Carbon::createFromFormat('H:i', $request->closing_time)->format('h:i A');
    }

    $chef->update($data);

    $bankDetails = [
        'account_holder_name' => $chef->name,
        'account_number'      => $request->account_number,
        'ifsc'                => $request->ifsc_code,
    ];

    // $razorpayResult = $this->chefPayoutService->registerChefBankDetails($chef, $bankDetails);

    return redirect()->route('chefs.index')->with('success', 'Chef updated successfully.');
}
    public function show($id)
    {
        $chef = Chef::with('restaurantTypes')->findOrFail($id);
    
        $foodItems = FoodDish::with('categories')
            ->where('chef_id', $id)
            ->get();
            
        $continuousAudits = ContinuousAudits::where('chef_id', $id)
            ->orderBy('date', 'desc')
            ->get();
            
       $kitchenPhotos = Chef::where('id', $id)
    ->select('id', 'kitchen_assessment_photographs')
    ->first();
    
         $orders = Order::with(['chef', 'user']) // relation ke saath
            ->where('chef_id', $id)
            ->where('payment_status', '!=', 'pending')
            ->orderBy('date', 'desc')
            ->get();
    
        // ✅ Loop through orders to build display text + amount breakdown
        foreach ($orders as $order) {
            $itemDetails = [];

            $items = $order->items;
            if (is_string($items)) {
                $items = json_decode($items, true);
            }

            if (is_array($items)) {
                foreach ($items as $item) {
                    $dish = FoodDish::find($item['id']);
                    if ($dish) {
                        $qty = $item['quantity'] ?? 1;
                        $itemDetails[] = $dish->name . ' (' . $qty . ')';
                    }
                }
            }

            $order->items_text = implode(', ', $itemDetails);

            // ✅ Use the amount actually charged/stored on the order (already includes
            // items + GST + platform fee) instead of recalculating from the food item's
            // CURRENT price, which drifts once a chef edits a dish price and previously
            // dropped the platform fee entirely.
            $order->gst_amount = (float) ($order->calculated_gst ?? 0);
            $order->platform_fee_amount = (float) ($order->platform_fee ?? 0);
            $order->items_subtotal = $order->amount - $order->gst_amount - $order->platform_fee_amount;
            $order->total_amount = $order->amount;
        }

        $totalOrders = $orders->count();
        
         // ✅ Fetch ratings & reviews
        $reviews = DB::table('reviews')
            ->leftJoin('ratings', function ($join) {
                $join->on('reviews.chef_id', '=', 'ratings.chef_id')
                    ->on('reviews.user_id', '=', 'ratings.user_id')
                    ->on('reviews.order_id', '=', 'ratings.order_id')
                    ->on('reviews.food_items_id', '=', 'ratings.food_items_id');
            })
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->where('reviews.chef_id', $id)
            ->select(
                'reviews.id as review_id',
                'users.name as name',
                'reviews.review_text as review',
                'reviews.created_at',
                'ratings.rating'
            )
            ->where('reviews.is_approved', 1)
            ->orderBy('reviews.created_at', 'desc')
            ->get();
    
         // ✅ Fetch ratings which do not have reviews
        $ratingsWithoutReviews = DB::table('ratings')
            ->leftJoin('reviews', function ($join) {
                $join->on('ratings.chef_id', '=', 'reviews.chef_id')
                    ->on('ratings.user_id', '=', 'reviews.user_id')
                    ->on('ratings.order_id', '=', 'reviews.order_id')
                    ->on('ratings.food_items_id', '=', 'reviews.food_items_id');
            })
            ->join('users', 'users.id', '=', 'ratings.user_id')
            ->where('ratings.chef_id', $id)
            ->whereNull('reviews.id')
            ->select(
                'ratings.id as rating_id',
                'users.name as name',
                DB::raw('NULL as review'),
                'ratings.created_at',
                'ratings.rating'
            )
            ->orderBy('ratings.created_at', 'desc')
            ->get();
    
        // ✅ Merge dono lists
        $allReviews = $reviews->merge($ratingsWithoutReviews)->sortByDesc('created_at');
    
        // ✅ Calculate average rating and counts - FIXED
        $averageRating = Rating::where('chef_id', $id)->avg('rating');
        
        // Get rating counts by rounding to nearest integer
        $ratingCounts = Rating::where('chef_id', $id)
            ->select(DB::raw('ROUND(rating) as rounded_rating'), DB::raw('COUNT(*) as total'))
            ->groupBy('rounded_rating')
            ->orderBy('rounded_rating', 'desc')
            ->pluck('total', 'rounded_rating')
            ->toArray();
    
        $totalReviews = Rating::where('chef_id', $id)->count();
        
        // Create complete rating counts array for 1-5 stars
        $completeRatingCounts = [];
        for ($i = 5; $i >= 1; $i--) {
            $completeRatingCounts[$i] = $ratingCounts[$i] ?? 0;
        }
    
        // $documents = [];
        // $docFields = [
        //     'gst_certificate' => 'GST Certificate',
        //     'fscai_certificate' => 'FSSAI Certificate',
        //     'aadhar_card' => 'Aadhar Card',
        //     'food_certificate' => 'Food Certificate',
        //     'other_document' => 'Other Document'
        // ];
    
        // foreach ($docFields as $field => $label) {
        //     if (!empty($chef->$field)) {
        //         $documents[] = [
        //             'label' => $label,
        //             'path' => $chef->$field
        //         ];
        //     }
        // }
        
        $documents = [];

        // 🔹 1. Personal Documents
        $personalDocs = [];
        if (!empty($chef->personal_documents)) {
            $personalDocs = is_array($chef->personal_documents)
                ? $chef->personal_documents
                : json_decode($chef->personal_documents, true);
        }
        
        if (!empty($personalDocs)) {
            foreach ($personalDocs as $doc) {
                $documents[] = [
                    'category' => 'Personal Documents',
                    'label' => 'Personal Document',
                    'path' => $doc,
                ];
            }
        } else {
            // 🔸 Agar personal documents nahi hain
            $documents[] = [
                'category' => 'Personal Documents',
                'label' => 'Personal Document',
                'path' => null, // pending
            ];
        }
        
        // 🔹 2. FSSAI Certificate
        $documents[] = [
            'category' => 'FSSAI Certificate',
            'label' => 'FSSAI Certificate',
            'path' => !empty($chef->fscai_certificate) ? $chef->fscai_certificate : null,
        ];
        
        // 🔹 3. Self Declaration
        $documents[] = [
            'category' => 'Self Declaration',
            'label' => 'Self Declaration',
            'path' => !empty($chef->self_declaration) ? $chef->self_declaration : null,
        ];

    
        $totalAmount = Order::where('chef_id', $id)->sum('amount');
        
        $commissionPercentage = $chef->commission ?? 0;
    
        // Commission amount
        $commissionAmount = ($totalAmount * $commissionPercentage) / 100;
        
        // Net earning = the chef's actual net payout (90% of menu base price),
        // identical to the admin Payout page and the chef app. Delivered only.
        $netEarnings = CommonHelper::chefPayoutBreakdown(
            Order::where('chef_id', $id)->where('status', 'delivered')->get(),
            $commissionPercentage
        )['net_payout'];
        
        
        // ✅ This Month Earning
        $thisMonthAmount = Order::where('chef_id', $id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        
        // ✅ Last Month Earning
        $lastMonthAmount = Order::where('chef_id', $id)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('amount');
            
        // ✅ Current Year Earning
        $thisYearAmount = Order::where('chef_id', $id)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // ✅ Net payout (90% of menu base price) per period — same basis as the
        // "Net Earnings" card above, the admin Payout page and the chef app.
        $netPayoutFor = fn($query) => CommonHelper::chefPayoutBreakdown($query->get(), $commissionPercentage)['net_payout'];

        $netThisMonthAmount = $netPayoutFor(
            Order::where('chef_id', $id)->where('status', 'delivered')
                ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)
        );
        $netLastMonthAmount = $netPayoutFor(
            Order::where('chef_id', $id)->where('status', 'delivered')
                ->whereYear('created_at', now()->subMonth()->year)->whereMonth('created_at', now()->subMonth()->month)
        );
        $netThisYearAmount = $netPayoutFor(
            Order::where('chef_id', $id)->where('status', 'delivered')
                ->whereYear('created_at', now()->year)
        );

        $photos = [];
        if (!empty($chef->kitchen_assessment_photographs)) {
            $photos = json_decode($chef->kitchen_assessment_photographs, true); 
        }
    
    
    
        return view('admin.chefs.view', compact(
            'chef',
            'foodItems',
            'orders',
            'allReviews', 
            'averageRating',
            'completeRatingCounts', 
            'totalReviews',
            'totalOrders',
            'documents',
            'totalAmount',
            'netEarnings', 
            'commissionAmount',
            'thisMonthAmount',
            'lastMonthAmount',
            'thisYearAmount',
            'netThisMonthAmount',
            'netLastMonthAmount',
            'netThisYearAmount',
            'photos',
            'continuousAudits',
            'kitchenPhotos'
        ));
    }


    public function destroy($id)
    {
        $chef = Chef::findOrFail($id);

        // Delete child entries first
        $chef->restaurantTypes()->detach(); // if it's many-to-many

        // Then delete chef
        $chef->delete();

        return redirect()->route('chefs.index')->with('success', 'Chef deleted successfully.');
    }
    public function toggleIsOpned(Request $request)
    {
        $chef = Chef::findOrFail($request->id);
        $chef->available = $request->available;
        $chef->save();

        return response()->json([
            'success' => true,
            'message' => 'Chef online status updated successfully.',
        ]);
    }

    public function updateStatusOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status'   => 'required|in:new,accepted,preparing,ready,delivered,rejected',
        ]);
    
        $order = Order::with('user')->findOrFail($request->order_id);
    
        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();
    
        // ---------- PUSH CONFIG ----------
        $statusMessages = [
            'accepted' => [
                'title' => 'Order Accepted ✅',
                'body'  => "Your order #{$order->id} has been accepted.",
            ],
            'preparing' => [
                'title' => 'Preparing Your Food 🍳',
                'body'  => "Your order #{$order->id} is being prepared.",
            ],
            'ready' => [
                'title' => 'Order Ready 🍽️',
                'body'  => "Your order #{$order->id} is ready.",
            ],
            'delivered' => [
                'title' => 'Order Delivered 🎉',
                'body'  => "Your order #{$order->id} has been delivered.",
            ],
            'rejected' => [
                'title' => 'Order Rejected ❌',
                'body'  => "Your order #{$order->id} has been rejected.",
            ],
        ];
    
        // ---------- SEND PUSH (ONLY IF MESSAGE EXISTS) ----------
        if (isset($statusMessages[$order->status])) {
            try {
                (new FCMService())->sendNotificationToUser(
                    $order->user_id, // CUSTOMER
                    $statusMessages[$order->status]['title'],
                    $statusMessages[$order->status]['body'],
                    [
                        'type'          => 'order_status_updated',
                        'orderId'       => (string) $order->id,
                        'status'        => $order->status,
                        'previousStatus'=> $oldStatus,
                        'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            } catch (\Throwable $e) {
                \Log::warning('Order status push failed', [
                    'order_id' => $order->id,
                    'status'   => $order->status,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully!',
        ]);
    }



    public function toggleApproval(Request $request, $id)
    {
        $review = DB::table('reviews')->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        DB::table('reviews')->where('id', $id)->update([
            'is_approved' => $request->is_approved
        ]);

        return response()->json([
            'message' => 'Status updated.',
            'status_text' => $request->is_approved ? 'Approved' : 'Disapproved'
        ]);
    }

    public function downloadDocument($filename)
    {
        $path = public_path('uploads/chefs/' . $filename);

        if (file_exists($path)) {
            return response()->download($path);
        }

        abort(404);
    }
    
    public function downloadPersonalDocs($id)
    {
        $chef = Chef::findOrFail($id);
        $personalDocs = json_decode($chef->personal_documents ?? '[]', true);
    
        if (empty($personalDocs)) {
            return back()->with('error', 'No documents found for this chef.');
        }
    
        $zip = new ZipArchive;
        $zipFileName = 'personal_documents_' . $chef->id . '.zip';
        $zipPath = storage_path("app/public/{$zipFileName}");
    
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($personalDocs as $file) {
                // File path fix
                $filePath = public_path(str_replace('public/', '', $file));
    
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($file));
                }
            }
            $zip->close();
        }
    
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

  public function downloadFssaiDocs($id)
{
    try {
        $chef = Chef::findOrFail($id);
        
        // Check if it's JSON or single file
        $fssaiDocs = [];
        $decoded = json_decode($chef->fscai_certificate ?? '', true);
        
        if (is_array($decoded) && !empty($decoded)) {
            $fssaiDocs = $decoded; // Multiple files
        } elseif (!empty($chef->fscai_certificate) && !is_array($chef->fscai_certificate)) {
            $fssaiDocs = [$chef->fscai_certificate]; // Single file
        }

        if (empty($fssaiDocs)) {
            return back()->with('error', 'No FSSAI documents found.');
        }

        // If only one file, download directly
        if (count($fssaiDocs) == 1) {
            $file = $fssaiDocs[0];
            
            // Remove 'public/' from path if present
            $filePath = str_replace('public/', '', $file);
            $fullPath = public_path($filePath);
            
            if (!file_exists($fullPath)) {
                return back()->with('error', 'File not found at path: ' . $filePath);
            }
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return response()->download($fullPath, 'fssai_certificate_' . $chef->id . '.' . $extension);
        }

        // Multiple files - create ZIP
        $zip = new \ZipArchive;
        $zipFileName = 'fssai_docs_' . $chef->id . '_' . time() . '.zip';
        
        // Use storage path for temp files
        $zipPath = storage_path("app/temp/{$zipFileName}");
        
        // Create temp directory if not exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $filesAdded = false;
            
            foreach ($fssaiDocs as $index => $file) {
                // Remove 'public/' from path if present
                $filePath = str_replace('public/', '', $file);
                $fullPath = public_path($filePath);
                
                if (file_exists($fullPath)) {
                    // Add with custom name to avoid duplicate names
                    $zip->addFile($fullPath, 'fssai_' . ($index + 1) . '_' . basename($filePath));
                    $filesAdded = true;
                } else {
                    \Log::warning('File not found: ' . $fullPath);
                }
            }
            
            $zip->close();
            
            if (!$filesAdded) {
                unlink($zipPath);
                return back()->with('error', 'No valid files found to download.');
            }
        } else {
            return back()->with('error', 'Could not create zip file.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        return back()->with('error', 'Error downloading files: ' . $e->getMessage());
    }
}

    public function downloadSelfDocs($id)
    {
        $chef = Chef::findOrFail($id);
        $selfDocs = json_decode($chef->self_declaration ?? '[]', true);

        if (empty($selfDocs)) {
            return back()->with('error', 'No Self Declaration documents found.');
        }

        $zip = new \ZipArchive;
        $zipFileName = 'self_declaration_' . $chef->id . '.zip';
        $zipPath = storage_path("app/public/{$zipFileName}");

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($selfDocs as $file) {
                $filePath = public_path(str_replace('public/', '', $file));
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($file));
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function updateDocumentStatus(Request $request, $id)
{
    $chef = Chef::findOrFail($id);

    $field = $request->input('field'); // example: personal_document_status
    $status = $request->input('status'); // approved/rejected

    if (in_array($field, ['personal_document_status', 'fssai_status', 'self_declaration_status'])) {
        $chef->$field = $status;
        $chef->save();
    }

    return response()->json(['success' => true]);
}


}