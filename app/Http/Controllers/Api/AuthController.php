<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use Mail;
use App\Models\Chef;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Order;



class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'user_name'    => 'required|string|max:255',
    //         'email'        => 'required|string|email|unique:users,email',
    //         'phone_number' => 'required|string|min:10|unique:users,phone_number',
    //         'user_role'    => 'required|exists:roles,id',
    //     ]);

    //     // if ($validator->fails()) {
    //     //     return response()->json([
    //     //         'status'  => 422,
    //     //         'success' => false,
    //     //         'error'   => $validator->errors()
    //     //     ], 422);
    //     // }

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'message' => $validator->errors()->first(), // ✅ sirf pehla error message return karega
    //         ], 422);
    //     }

    //     // $role = Role::where('id', $request->user_role)->whereNull('deleted_at')->first();
    //     // if (!$role) {
    //     //     return response()->json([
    //     //         'status'  => 422,
    //     //         'success' => false,
    //     //         'error'   => ['user_role' => ['The selected role is invalid or has been deleted.']]
    //     //     ], 422);
    //     // }

    //     $role = Role::where('id', $request->user_role)->whereNull('deleted_at')->first();
    //     if (!$role) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'message' => 'The selected role is invalid or has been deleted.', // ✅ simple message
    //         ], 422);
    //     }

    //     // Use hardcoded OTP
    //     $otp = 123456;

    //     User::create([
    //         'name'             => $request->user_name,
    //         'email'            => $request->email,
    //         'phone_number'     => $request->phone_number,
    //         'user_role'        => $request->user_role,
    //         'otp'              => $otp,
    //         'otp_verified'     => 0,
    //         'otp_expires_at'   => now()->addMinutes(10),
    //     ]);

    //     return CommonHelper::apiResponse(200, true, 'OTP sent successfully.', null);
    // }

    // public function signin(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required|string|min:10',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'error'   => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::where('phone_number', $request->phone_number)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status'  => 404,
    //             'success' => false,
    //             'message' => 'User not found.'
    //         ], 404);
    //     }

    //     // Use hardcoded OTP
    //     $otp = 123456;

    //     $user->update([
    //         'otp' => $otp,
    //         'otp_verified' => 0,
    //         'otp_expires_at' => now()->addMinutes(10),
    //     ]);

    //     return CommonHelper::apiResponse(200, true, 'OTP sent successfully.', null);
    // }


    private function generateOtp()
    {
        return rand(100000, 999999); // 6 digit random OTP
    }

    private function sendOtpSms($phone, $otp)
    {

        $message = "Dear User, your OTP for login to Nutrition Nook is {$otp}. It is valid for 30 minutes. Please do not share this OTP with anyone. Regards, Nutrition Nook";

        $response = Http::timeout(30)->get('http://182.18.162.128/api/mt/SendSMS', [
            'user'      => 'NUTRNK',
            'password'  => '123456',
            'senderid'  => 'NUTRNK',
            'channel'   => 'trans',
            'DCS'       => 0,
            'flashsms'  => 0,
            'number'    => $phone,
            'text'      => $message,
            'route'     => 29,
        ]);

        // 👇 IMPORTANT DEBUG
        \Log::info('SMS Response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'phone' => $phone
        ]);

        return $response->successful();
    }

    /**
     * Format Indian phone numbers
     */
    private function formatIndianPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If number starts with 91 and has 12 digits, keep as is
        if (strlen($phone) == 12 && substr($phone, 0, 2) == '91') {
            return $phone;
        }

        // If number has 10 digits, add 91 prefix
        if (strlen($phone) == 10) {
            return '91' . $phone;
        }

        // If number has 11 digits and starts with 0, remove 0 and add 91
        if (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
            return '91' . substr($phone, 1);
        }

        // Invalid format
        return false;
    }



    public function register(Request $request)
    {
        // 🔥 STEP 0: Remove unverified users with same email or phone
        // User::where('otp_verified', 0)
        //     ->where(function ($q) use ($request) {
        //         if ($request->email) {
        //             $q->orWhere('email', $request->email);
        //         }

        //         if ($request->phone_number) {
        //             $q->orWhere('phone_number', $request->phone_number);
        //         }
        //     })
        //     ->delete();

        // ✅ STEP 1: Validation
        $validator = Validator::make($request->all(), [
            'user_name'    => 'required|string|max:255',
            'email'        => 'required|string|email|unique:users,email',
            'phone_number' => 'required|string|min:10|unique:users,phone_number',
            'user_role'    => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return CommonHelper::apiResponse(
                422,
                false,
                $validator->errors()->first(),
                []
            );
        }

        // ✅ STEP 2: Role validation
        $role = Role::where('id', $request->user_role)
            ->first();

        if (!$role) {
            return CommonHelper::apiResponse(
                422,
                false,
                'The selected role is invalid or has been deleted.',
                []
            );
        }

        // ✅ STEP 3: OTP Logic
        $specialNumbers = ['1234567890', '1421421421', '2312312323', '1111111111', '2222222222', '1111222222', '2222233333', '6767676767', '6868686868', '8000000001', '8000000002', '8000000003', '8000000004', '8000000005', '9000000001', '9000000002', '9000000003', '9000000004', '9000000005'];

        $otp = in_array($request->phone_number, $specialNumbers)
            ? 123456
            : $this->generateOtp();

        // ✅ STEP 4: Create User
        User::create([
            'name'           => $request->user_name,
            'email'          => $request->email,
            'phone_number'   => $request->phone_number,
            'user_role'      => $request->user_role,
            'otp'            => $otp,
            'otp_verified'   => 0,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // ✅ STEP 5: Send OTP
        if (!in_array($request->phone_number, $specialNumbers)) {
            $this->sendOtpSms($request->phone_number, $otp);
        }


        return CommonHelper::apiResponse(
            200,
            true,
            'OTP sent successfully.',
            null
        );
    }




    public function signin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return CommonHelper::apiResponse(
                422,
                false,
                $validator->errors()->first(),
                []
            );
        }


        // 👇 SPECIAL OTP CONDITION
        $specialNumbers = ['1234567890', '1421421421', '2312312323', '1111111111', '2222222222', '1111222222', '2222233333', '6767676767', '6868686868', '8000000001', '8000000002', '8000000003', '8000000004', '8000000005', '9000000001', '9000000002', '9000000003', '9000000004', '9000000005', '9876543210', '9000000001', '9000000002', '9000000003', '9000000004', '9000000005'];

        $otp = in_array($request->phone_number, $specialNumbers)
            ? 123456
            : $this->generateOtp();


        $expiryTime = now()->addMinutes(30);

        if ($request->login_type === 'customer') {


            /* ---------- CHECK USER ---------- */
            $user = User::where('phone_number', $request->phone_number)
                ->first();

            if (empty($user)) {
                return CommonHelper::apiResponse(
                    404,
                    false,
                    'User not found.',
                    []
                );
            }


            // ✅ Send OTP
            $user->update([
                'otp'            => $otp,
                'otp_verified'   => 0,
                'otp_expires_at' => $expiryTime,
            ]);

            // if (!empty($user->email)) {
            //     try {
            //         Mail::send('emails.otp', ['otp' => $otp, 'user' => $user, 'type' => 'customer'], function($message) use ($user) {
            //             $message->to($user->email)
            //                     ->subject('Your Login OTP for Food App');
            //         });
            //     } catch (\Exception $e) {
            //         // Log email error but don't stop the process
            //         \Log::error('Email sending failed: ' . $e->getMessage());
            //     }
            // }



            if (!in_array($request->phone_number, $specialNumbers)) {
                $this->sendOtpSms($request->phone_number, $otp);
            }

            return CommonHelper::apiResponse(
                200,
                true,
                'OTP sent successfully to User.',
                []
            );
        }
        if ($request->login_type === 'chef') {

            /* ---------- CHECK CHEF ---------- */
            $chef = Chef::where('phone_number', $request->phone_number)
                ->first();


            if (empty($chef)) {
                return CommonHelper::apiResponse(
                    404,
                    false,
                    'Chef not found.',
                    []
                );
            }

            // ❌ Chef deleted check
            if ($chef->is_delete == 1) {
                return CommonHelper::apiResponse(
                    403,
                    false,
                    'Your chef account has been deleted. Please contact support.',
                    []
                );
            }

            // ✅ Send OTP
            // $chef->update([
            //     'otp'            => $otp,
            //     'otp_verified'   => 0,
            //     'otp_expires_at' => $expiryTime,
            // ]);
            \DB::table('chefs')->where('id', $chef->id)
                ->update(['otp' => $otp, 'otp_verified' => 0, 'otp_expires_at' => $expiryTime]);

            // 📧 Email send for chef
            // if (!empty($chef->email)) {
            //     try {
            //         Mail::send('emails.otp', ['otp' => $otp, 'user' => $chef, 'type' => 'chef'], function($message) use ($chef) {
            //             $message->to($chef->email)
            //                     ->subject('Your Login OTP for Food App');
            //         });
            //     } catch (\Exception $e) {
            //         // Log email error but don't stop the process
            //         \Log::error('Email sending failed: ' . $e->getMessage());
            //     }
            // }



            if (!in_array($request->phone_number, $specialNumbers)) {

                $this->sendOtpSms($request->phone_number, $otp);
            }
        }


        return CommonHelper::apiResponse(
            200,
            true,
            'OTP sent successfully to Chef.',
            []
        );
    }





    // public function verifyOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required|string|min:10',
    //         'otp' => 'required|numeric|digits:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'error'   => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::where('phone_number', $request->phone_number)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 404,
    //             'success' => false,
    //             'message' => 'User with this phone number does not exist.',
    //         ]);
    //     }

    //     if ($user->otp !== $request->otp) {
    //         return response()->json([
    //             'status' => 401,
    //             'success' => false,
    //             'message' => 'Invalid OTP.',
    //         ]);
    //     }

    //     if ($user->otp_expires_at && $user->otp_expires_at < now()) {
    //         return response()->json([
    //             'status' => 401,
    //             'success' => false,
    //             'message' => 'OTP has expired.',
    //         ]);
    //     }

    //     // âœ… All good â€” verify OTP
    //     $user->update([
    //         'otp_verified' => 1,
    //     ]);

    //     $user = $user->fresh(); // Load all fields

    //     $token = $user->createToken($user->email)->plainTextToken;

    //     $data = $user->toArray(); // Convert user to array
    //     $data['token'] = $token;  // Add token inside data

    //     // return response()->json([
    //     //     'status' => 200,
    //     //     'success' => true,
    //     //     'message' => 'OTP verified successfully.',
    //     //     'data' => $data
    //     // ]);

    //     return CommonHelper::apiResponse(200, true, 'OTP verified successfully.', $data);
    // }

    // public function resendOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required|string|min:10',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'error'   => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::where('phone_number', $request->phone_number)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 404,
    //             'success' => false,
    //             'message' => 'User not found.'
    //         ], 404);
    //     }

    //     // $otp = rand(100000, 999999);
    //     $otp = 123456;

    //     $user->update([
    //         'otp' => $otp,
    //         'otp_verified' => 0,
    //         'otp_expires_at' => now()->addMinutes(10),
    //     ]);

    //     // return response()->json([
    //     //     'status' => 200,
    //     //     'success' => true,
    //     //     'message' => 'OTP resent successfully.',
    //     //     'otp' => $otp,
    //     // ]);

    //     return CommonHelper::apiResponse(200, true, 'OTP resent successfully.', $otp);
    // }

    // public function verifyOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required|string|min:10',
    //         'otp' => 'required|numeric|digits:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'error'   => $validator->errors()
    //         ], 422);
    //     }

    //     // User ya Chef find karo
    //     $model = User::where('phone_number', $request->phone_number)->first();

    //     if (empty($model)) {
    //         $model = Chef::where('phone_number', $request->phone_number)->first();
    //     }


    //     if (!$model) {
    //         return response()->json([
    //             'status' => 404,
    //             'success' => false,
    //             'message' => 'User or Chef with this phone number does not exist.',
    //         ]);
    //     }

    //     if ($model->otp !== $request->otp) {
    //         return response()->json([
    //             'status' => 401,
    //             'success' => false,
    //             'message' => 'Invalid OTP.',
    //         ]);
    //     }

    //     // if ($model->otp_expires_at && $model->otp_expires_at < now()) {
    //     //     return response()->json([
    //     //         'status' => 401,
    //     //         'success' => false,
    //     //         'message' => 'OTP has expired.',
    //     //     ]);
    //     // }

    //     $model->update([
    //         'otp_verified' => 1,
    //     ]);

    //     $model = $model->fresh();

    //     // Agar ye User model hai aur uska user_role hai toh title replace karo
    //     if ($model instanceof User && !empty($model->user_role)) {
    //         $roleTitle = \DB::table('roles')->where('id', $model->user_role)->value('title');
    //         $model->user_role = $roleTitle ?? null;
    //     }

    //     // Token sirf User model ke liye banega (Chef ke liye skip)
    //     $token = $model->createToken($model->email)->plainTextToken;

    //     $data = $model->toArray();
    //     $data['token'] = $token;

    //     return CommonHelper::apiResponse(200, true, 'OTP verified successfully.', $data);
    // }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:10',
            'otp' => 'required|numeric|digits:6',
            'login_with' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'error'   => $validator->errors()
            ], 422);
        }

        // User ya Chef find karo
        if ($request->login_with == 'customer') {

            $model = User::where('phone_number', $request->phone_number)->first();
        } elseif ($request->login_with == 'chef') {

            $model = Chef::where('phone_number', $request->phone_number)->first();
        } else {

            $model = User::where('phone_number', $request->phone_number)->first();

            if (empty($model)) {
                $model = Chef::where('phone_number', $request->phone_number)->first();
            }
        }

        if (!$model) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'User or Chef with this phone number does not exist.',
            ]);
        }

        if ($model->otp !== $request->otp) {
            return response()->json([
                'status' => 401,
                'success' => false,
                'message' => 'Invalid OTP.',
            ]);
        }

        // if ($model->otp_expires_at && $model->otp_expires_at < now()) {
        //     return response()->json([
        //         'status' => 401,
        //         'success' => false,
        //         'message' => 'OTP has expired.',
        //     ]);
        // }

        $model->update([
            'otp_verified' => 1,
        ]);

        $model = $model->fresh();

        // Agar ye User model hai aur uska user_role hai toh title replace karo
        if ($model instanceof User && !empty($model->user_role)) {
            $roleTitle = \DB::table('roles')->where('id', $model->user_role)->value('title');
            $model->user_role = $roleTitle ?? null;
        }

        // Token sirf User model ke liye banega (Chef ke liye skip)
        $token = $model->createToken($model->email)->plainTextToken;

        $data = $model->toArray();
        $data['token'] = $token;

        return CommonHelper::apiResponse(200, true, 'OTP verified successfully.', $data);
    }


    // public function resendOtp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required|string|min:10',
    //     ]);

    //     if ($validator->fails()) {
    //         return CommonHelper::apiResponse(422, false, $validator->errors()->first(), []);
    //     }

    //     $model = User::where('phone_number', $request->phone_number)->first();
    //     $type = 'User';

    //     if (!$model) {
    //         $model = Chef::where('phone_number', $request->phone_number)->first();
    //         $type = 'Chef';
    //     }

    //     if (!$model) {
    //         return CommonHelper::apiResponse(404, false, 'User or Chef not found.', []);
    //     }

    //     // 👇 SAME SPECIAL OTP CONDITION (User + Chef)
    //     $specialNumbers = ['1234567890','1421421421','2312312323','1111111111','2222222222','1111222222','2222233333','6767676767','6868686868','8000000001','8000000002','8000000003','8000000004','8000000005','9000000001','9000000002','9000000003','9000000004','9000000005'];

    //     $otp = in_array($request->phone_number, $specialNumbers)
    //         ? 123456
    //         : $this->generateOtp();

    //     $model->update([
    //         'otp' => $otp,
    //         'otp_verified' => 0,
    //         'otp_expires_at' => now()->addMinutes(30),
    //     ]);
    //         if(!in_array($request->phone_number, $specialNumbers)){
    //              $smsSent = $this->sendOtpSms($request->phone_number, $otp);
    //         }

    //     if (!$smsSent) {
    //         return CommonHelper::apiResponse(500, false, 'Failed to send OTP. Please try again.', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, "OTP resent successfully to {$type}.", []);
    // }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:10',
            'login_with' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return CommonHelper::apiResponse(422, false, $validator->errors()->first(), []);
        }

        if ($request->login_with == 'customer') {

            $model = User::where('phone_number', $request->phone_number)->first();
            $type = 'User';
        } elseif ($request->login_with == 'chef') {

            $model = Chef::where('phone_number', $request->phone_number)->first();
            $type = 'Chef';
        } else {

            $model = User::where('phone_number', $request->phone_number)->first();
            $type = 'User';

            if (!$model) {
                $model = Chef::where('phone_number', $request->phone_number)->first();
                $type = 'Chef';
            }
        }

        if (!$model) {
            return CommonHelper::apiResponse(404, false, 'User or Chef not found.', []);
        }

        // 👇 SAME SPECIAL OTP CONDITION (User + Chef)
        $specialNumbers = ['1234567890', '1421421421', '2312312323', '1111111111', '2222222222', '1111222222', '2222233333', '6767676767', '6868686868', '8000000001', '8000000002', '8000000003', '8000000004', '8000000005', '9000000001', '9000000002', '9000000003', '9000000004', '9000000005'];

        $otp = in_array($request->phone_number, $specialNumbers)
            ? 123456
            : $this->generateOtp();

        $model->update([
            'otp' => $otp,
            'otp_verified' => 0,
            'otp_expires_at' => now()->addMinutes(30),
        ]);

        if (!in_array($request->phone_number, $specialNumbers)) {
            $smsSent = $this->sendOtpSms($request->phone_number, $otp);
        } else {
            $smsSent = true;
        }

        if (!$smsSent) {
            return CommonHelper::apiResponse(500, false, 'Failed to send OTP. Please try again.', []);
        }

        return CommonHelper::apiResponse(200, true, "OTP resent successfully to {$type}.", []);
    }





    // public function loggeduser(Request $request)
    // {
    //     // Check if the user is authenticated
    //     if (!Auth::check()) {
    //         return response()->json(['status' => 401, 'success' => false, 'message' => 'Unauthorized. Please log in again.'], 401);
    //     }

    //     // Get the authenticated user
    //     $user  = Auth::user();

    //     // If the user doesn't exist
    //     if (!$user) {
    //         return response()->json(['status' => 404, 'success' => false, 'message' => 'User not found'], 404);
    //     }

    //     // Fetch the user data
    //     $data = User::where('id', $user->id)->first();

    //     // Return user data
    //     return CommonHelper::apiResponse(200, true, 'LoggedUser data fetched successfully!', $data);
    // }



    //     public function loggeduser(Request $request)
    // {
    //     if (!Auth::check()) {
    //         return response()->json([
    //             'status'  => 401,
    //             'success' => false,
    //             'message' => 'Unauthorized. Please log in again.'
    //         ], 401);
    //     }

    //     $authUser = Auth::user();

    //     // Pehle users table me dhoondo
    //     $data = User::find($authUser->id);

    //     if ($data) {
    //         // Agar user_role hai toh roles table se title le aao
    //         if (!empty($data->user_role)) {
    //             $roleTitle = \DB::table('roles')->where('id', $data->user_role)->value('title');
    //             $data->user_role = $roleTitle ?? null;
    //         }

    //         // ✅ User image ko asset path ke sath bhejo
    //         if (!empty($data->image)) {
    //             $data->image = asset('/images/' . $data->image);
    //         }

    //     } else {
    //         // Agar users table me nahi mila toh chefs table me dhoondo
    //         $data = Chef::find($authUser->id);

    //         if ($data) {
    //             // ✅ Opening & Closing Time ko 24-hour format me convert karo
    //             if (!empty($data->opening_time)) {
    //                 $data->opening_time = date("H:i", strtotime($data->opening_time));
    //             }
    //             if (!empty($data->closing_time)) {
    //                 $data->closing_time = date("H:i", strtotime($data->closing_time));
    //             }

    //             // ✅ Chef ke images (cover_image & profile_image) ko asset path ke sath bhejo
    //             if (!empty($data->cover_image)) {
    //                 $data->cover_image = asset($data->cover_image);
    //             }
    //             if (!empty($data->profile_image)) {
    //                 $data->profile_image = asset($data->profile_image);
    //             }
    //         }
    //     }

    //     if (!$data) {
    //         return response()->json([
    //             'status'  => 404,
    //             'success' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'LoggedUser data fetched successfully!', $data);
    // }


    public function loggeduser(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'Unauthorized. Please log in again.'
            ], 401);
        }

        $authUser = Auth::user();


        $data = null;

        /* =======================
       USER LOGIN
    ======================== */
        if ($authUser instanceof User) {

            $data = $authUser;

            if (!empty($data->user_role)) {
                $data->user_role = \DB::table('roles')
                    ->where('id', $data->user_role)
                    ->value('title');
            }

            if (!empty($data->image)) {
                $data->image = asset('/images/' . $data->image);
            }
        }

        /* =======================
       CHEF LOGIN
    ======================== */
        if ($authUser instanceof Chef) {

            $data = $authUser;


            if (!empty($data->opening_time)) {
                $data->opening_time = date('H:i', strtotime($data->opening_time));
            }
            if (!empty($data->closing_time)) {
                $data->closing_time = date('H:i', strtotime($data->closing_time));
            }

            // Images
            if (!empty($data->cover_image)) {
                $data->cover_image = asset($data->cover_image);
            }
            if (!empty($data->profile_image)) {
                $data->profile_image = asset($data->profile_image);
            }

            if ($data->is_verify == 0 && $data->available == 1) {
                $data->available = 0;
                $data->save(); // DB update
            }


            // ✅ Orders data automatically from model accessors
        }


        if (!$data) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return CommonHelper::apiResponse(
            200,
            true,
            'LoggedUser data fetched successfully!',
            $data
        );
    }


    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // dd
            // 1. Check if the user has any registered devices
            $userDevices = \App\Models\UserDevice::where('user_id', $user->id);

            if ($userDevices->exists()) {
                // 2. If records exist, delete them
                $userDevices->delete();
                \Log::info("FCM tokens cleared for User ID: {$user->id}");
            }

            // 3. Delete the Authentication tokens (Sanctum/Passport)
            $user->tokens()->delete();

            return CommonHelper::apiResponse(200, true, 'Logout successful and device records cleared!', null);
        }

        return CommonHelper::apiResponse(401, false, 'Unauthorized', null);
    }
}
