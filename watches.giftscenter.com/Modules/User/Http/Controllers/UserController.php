<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\RegisterUserRequest;
use Modules\User\Http\Requests\LoginUserRequest;
use Modules\User\Http\Services\UserService;
use Modules\User\Entities\UserLoyaltyPoint;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Frontend\Entities\Article;
use Modules\Cart\Entities\Wishlist;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\User\Entities\User;
use Helper;
use Mail;
use Carbon\Carbon;

use Modules\Frontend\Entities\Reward;
use Modules\Frontend\Entities\Tier;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function _loginSubmit(Request $request)
    {

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $rand_key = Str::random(6);// Use Laravel helper function to generate a random string
            $user->update(['rand_key' => $rand_key]);

            $loyalty_point = 0;

            $latestPointRecord = DB::table('customer_points')
                ->where('customer_id', $user->customer_id)
                ->orderBy('added_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestPointRecord) {
                $loyalty_point = (int)$latestPointRecord->post_balance;
            }
            $request->session()->flash('success', 'Successfully logged in');//exit;
            return redirect()->route('home');
        } else {
            $request->session()->flash('error', 'Invalid email and password, please try again!');
            return redirect()->back();
        }
    }

    public function logout()
    {
        Session::forget('user');
        Auth::logout();
        request()->session()->flash('success', 'Logout successfully');
        return back();
    }

    public function registerSubmit(Request $request)
    {
        // return $request->all();
        $this->validate($request, [
            'name' => 'string|required|min:2',
            'email' => 'string|required|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);
        $data = $request->all();
        // dd($data);
        $check = $this->create($data);
        Session::put('user', $data['email']);
        if ($check) {
            request()->session()->flash('success', 'Successfully registered');
            return redirect()->route('home');
        } else {
            request()->session()->flash('error', 'Please try again!');
            return back();
        }
    }

    /**
     * Sign up request by user
     *
     * @param RegisterUserRequest $request
     * @return JsonResponse
     */
    public function registerbckp(RegisterUserRequest $request): JsonResponse
    {
        $response = $this->userService->register($request->validated());

        return response()->json($response);

    }

    public function register()
    {
        if (auth()->check()) {
            // User is already authenticated, redirect to the previous page or home
            return Redirect::intended('/home');
        }

        return view('frontend.pages.register');
    }


    public function loginbckp(Request $request)
    {
        $response = $this->userService->login($request->all());

        if ($response['res']) {
            // Login successful, redirect to the login success view
            return redirect()->route('user.login');
        } else {
            // Login failed, redirect back with an error message
            return redirect()->back()->with('error', $response['msg']);
        }
    }

    /**
     * Sign in request by user.
     *
     * @param LoginUserRequest $request
     * @return JsonResponse
     */
    // public function login(Request $request): JsonResponse
    // {
    // $response = $this->userService->login($request);

    // return response()->json($response);
    // }
    public function login()
    {

        return view('frontend.pages.login');
    }

    /**
     * Forget password request by user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $response = $this->userService->forgetPassword($request);

        return response()->json($response);
    }

    /**
     * Reset password request by user.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {

        $response = $this->userService->resetPassword($request->validated());

        return response()->json($response);
    }


    public function sendOTP(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes',
            'phone_code' => 'sometimes',
            'phone' => 'sometimes',
            'country' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(['res' => false, 'msg' => $validator->errors()->first(), 'data' => '']);
        }

        // Extract data from the validated request
        $email = $request->input('email');
        $phoneCode = $request->input('phone_code');
        $phone = $request->input('phone');
        $country = $request->input('country');

        if ($email != '') {
            $existingCustomer = User::where('email', $email)->first();
            if ($existingCustomer) {
                $otp = Helper::generateOTP(6);
                $existingCustomer->OTP = $otp;
                $existingCustomer->save();

                $content = '<#> ' . $otp . ' is your verification code from Gifts Center for login. Please do not share it with anyone.';
                Helper::sendMail($email, $existingCustomer->customer_name, 'Login OTP', $content);
                $data = ['reg_type' => "email", 'customer' => $existingCustomer->id, 'action' => 'login'];

                return response()->json(['res' => true, 'msg' => 'OTP sent successfully', 'data' => $data]);
            } else {
                $otp = Helper::generateOTP(6);

                $result = $this->userService->saveOTP($email, $phoneCode, $phone, $country, $otp);
                if ($result) {
                    $content = '<#> ' . $otp . ' is your verification code from Gifts Center for registration. Please do not share it with anyone.';
                    Helper::sendMail($email, 'User', 'Registration OTP', $content);

                    $lastInsertedId = DB::getPdo()->lastInsertId();
                    $result = ['reg_type' => "email", 'customer' => $lastInsertedId, 'action' => 'register'];

                    return response()->json(['res' => true, 'msg' => 'OTP sent successfully', 'data' => $result]);
                } else {
                    return response()->json(['res' => false, 'msg' => 'Failed to send OTP', 'data' => ""]);
                }
            }
        } elseif ($phone != '') {
            $existingCustomer = User::where('phone', $phone)->first();
            if ($existingCustomer) {
                $time = Carbon::now();
                $convertedTime = $time->addMinutes(1)->format('Y-m-d H:i:s');
                $otp = Helper::generateOTP(6);
                $existingCustomer->OTP = $otp;
                $existingCustomer->otp_timer = $convertedTime;
                $existingCustomer->save();
                //$existingCustomer->update(['OTP' => $otp]);
                //$this->userService->sendOTP($email, $phoneCode, $phone, $otp,'phone');
                $message = $otp . " is your verification code for login. Please do not share it with anyone.";
                $phoneNumber = $phoneCode . $phone;
                Helper::sendSMS($phoneNumber, $message);
                $data = ['reg_type' => "phone", 'customer' => $existingCustomer->id, 'action' => 'login'];
                return response()->json(['res' => true, 'msg' => 'OTP sent successfully', 'data' => $data]);
            } else {
                $otp = Helper::generateOTP(6);
                $result = $this->userService->saveOTP($email, $phoneCode, $phone, $country, $otp);
                if ($result) {
                    //$this->userService->sendOTP($email, $phoneCode, $phone, $otp,'phone');
                    $message = $otp . " is your verification code for registration. Please do not share it with anyone.";
                    $phoneNumber = $phoneCode . $phone;
                    Helper::sendSMS($phoneNumber, $message);
                    $lastInsertedId = \DB::getPdo()->lastInsertId();
                    $result = ['reg_type' => "phone", 'customer' => $lastInsertedId, 'action' => 'register'];
                    return response()->json(['res' => true, 'msg' => 'OTP sent successfully', 'data' => $result]);
                } else {
                    return response()->json(['res' => false, 'msg' => 'Failed to send OTP', 'data' => ""]);
                }
            }

        } else {

        }


    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'customer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['res' => false, 'msg' => 'Validation failed', 'errors' => $validator->errors()]);
        }
        $otp = $request->input('otp');
        $customer = $request->input('customer');
        $action = $request->input('action');
        if ($action == 'login') {
            $user = User::find($customer);
            if (!$user) {
                return response()->json(['res' => false, 'msg' => 'user not found', 'data' => ""]);
            }
            if ($user->OTP != '0' && $user->OTP == $otp) {
                $randomKey = Str::random(6);
                $user->rand_key = $randomKey;
                $user->save();
                // Extract the needed columns from the user instance
                $loginData = [
                    'id' => $user->id,
                    'customer_id' => $user->customer_id,
                    'customer_name' => $user->customer_name,
                    'organization' => $user->organization,
                    // Add other necessary columns here
                ];

                // Set the needed columns in the Auth facade
                //Auth::getProvider()->setAuthCredentials($loginData);
                // Perform the login without a password
                Auth::loginUsingId($user->id);
                if (session()->has('is_guest')) {
                    Session::forget(['is_guest']);
                }

                $data = [
                    'reg_id' => $user->id,
                    'customer_id' => $user->customer_id,
                    'gender' => $user->gender,
                    'country' => $user->country,
                    'customer_name' => $user->customer_name,
                    'email' => $user->email,
                    'phone_code' => $user->phone_code,
                    'phone' => $user->phone
                ];

                return response()->json(['res' => true, 'msg' => "Successfully logged in", 'data' => $data]);

            } else {
                return response()->json(['res' => false, 'msg' => 'Provided confirmation code is not valid', 'data' => []]);
            }

        } else {
            $user = DB::table('gc_register')
                ->where('id', $customer)
                ->first();
            if (!$user) {
                return response()->json(['res' => false, 'msg' => 'user not found', 'data' => ""]);
            }

            if ($user->otp != '0' && $user->otp == $otp) {
                // OTP is valid
                $reg_id = $customer;
                $customer_id = "";
                $gender = "male";
                $country = $user->country != '' ? $user->country : "Jordan";
                $customer_name = "";
                $email = $user->email;
                $phone_code = $user->phone_code != '' ? $user->phone_code : "962";
                $phone = $user->phone;

                if ($user->customer_id != "") {
                    $pcustomer = User::where('customer_id', $user->customer_id)->first();

                    if ($pcustomer) {
                        $customer_id = $pcustomer->customer_id;
                        $gender = $pcustomer->gender;
                        $country = $pcustomer->country;
                        $customer_name = $pcustomer->customer_name;
                        $email = $pcustomer->email;
                        $phone_code = $pcustomer->phone_code;
                        $phone = $pcustomer->phone;
                    }
                }

                $data = [
                    "reg_id" => $reg_id,
                    "customer_id" => $customer_id,
                    "gender" => $gender,
                    "country" => $country,
                    "customer_name" => $customer_name,
                    "email" => $email,
                    "phone_code" => $phone_code,
                    "phone" => $phone
                ];

                return response()->json(['res' => true, 'msg' => 'Thank you for your patience, one more step', 'data' => $data]);
            } else {
                return response()->json(['res' => false, 'msg' => 'Provided confirmation code is not valid', 'data' => []]);
            }
        }


    }


    public function signUp(Request $request)
    {
        $error_msg = '';
        $success_msg = '';
        $result_array = [];

        $regid = $request->input('regid');
        $type = $request->input('type');
        $customer = $request->input('customer');
        $customerName = $request->input('customer_name');
        $gender = $request->input('gender');
        $company = $request->input('company');
        $email = $request->input('email');
        $phoneCode = $request->input('phone_code');
        $country = $request->input('country');
        $phone = $request->input('phone');
        $dob = $request->input('dob');

        $phone = preg_replace("/^0/", "", $phone);

        // Validation
        if (empty($customerName)) {
            $error_msg = "Please provide your name";
        } elseif (empty($email)) {
            $error_msg = "Please provide your email address";
        } elseif (empty($phone)) {
            $error_msg = "Please provide your phone number";
        }

        // Check if email is already registered
        if (empty($error_msg) && !empty($email)) {
            $existingCustomerQry = DB::table('pos_customer')
                ->where('email', $email);
            if (!empty($customer)) {
                $existingCustomerQry->when($customer, function ($query) use ($customer) {
                    return $query->where('customer_id', '!=', $customer);
                });
            }
            $existingCustomer = $existingCustomerQry->exists();

            if ($existingCustomer) {
                $error_msg = "Email address is already registered with us";
            }
        }

        // Check if phone number is already registered
        if (empty($error_msg) && !empty($phone)) {
            $existingCustomerQry = DB::table('pos_customer')
                ->where('phone', $phone);
            if (!empty($customer)) {
                $existingCustomerQry->when($customer, function ($query) use ($customer) {
                    return $query->where('customer_id', '!=', $customer);
                });
            }
            $existingCustomer = $existingCustomerQry->exists();

            if ($existingCustomer) {
                $error_msg = "Phone number is already registered with us";
            }
        }

        if (empty($error_msg)) {
            $salutation = ($gender === 'male') ? 'Mr' : (($gender === 'female') ? 'Mrs' : '');

            $password = Hash::make('Giftscenter2024!');

            $emailArr = explode("@", $email);
            $domain = $emailArr[1] ?? '';

            $organization = DB::table('organizations')
                ->where('domain', $domain)
                ->value('id') ?? '';

            if (!empty($customer)) {
                // Update existing customer
                DB::table('pos_customer')
                    ->where('customer_id', $customer)
                    ->update([
                        'date_added' => now(),
                        'mr_ms' => $salutation,
                        'customer_name' => clean($customerName),
                        'email' => $email,
                        'phone_code' => $phoneCode,
                        'phone' => $phone,
                        'dob' => $dob,
                        'password' => $password,
                        'company' => $company,
                        'organization' => $organization,
                        'country' => $country,
                        'gender' => $gender,
                        'reg_flag' => '2',
                    ]);
                $customerDetails = User::where('customer_id', $customer)->first();
            } else {
                $sequence = DB::table('pos_customer')
                    ->where('customer_id', 'LIKE', '%GC%')
                    ->max(DB::raw("CONVERT(SUBSTRING_INDEX(customer_id,'-',-1),UNSIGNED INTEGER)"));

                $customer_id = 'GC-' . ($sequence + 1);

                $pexpiry = 365;
                $ptype = 'create-an-account';
                $ppoint = 0;

                $reward = DB::table('gc_rewards')
                    ->where('slug', 'create-an-account')
                    ->where('tier_id', '1')
                    ->where('status', '1')
                    ->value('points');

                if ($reward) {
                    // Insert points record
                    DB::table('gc_points')->insert([
                        'user' => $customer_id,
                        'type' => 'create-an-account',
                        'points' => $reward,
                        'added_date' => now(),
                    ]);

                    // Fetch previous balance
                    $pre_balance = DB::table('customer_points')
                        ->where('customer_id', $customer_id)
                        ->orderBy('id', 'DESC')
                        ->value('post_balance');

                    // Set default value if it's the first transaction
                    $pre_balance = $pre_balance ?? 0;

                    $post_balance = $pre_balance + $reward;
                    $valid_date = now()->addDays($pexpiry);

                    // Insert new record for gained loyalty
                    $customerPoints = new UserLoyaltyPoint();
                    $customerPoints->customer_id = $customer_id;
                    $customerPoints->point_in = $reward;
                    $customerPoints->point_out = 0;
                    $customerPoints->point_type = 'create-an-account';
                    $customerPoints->invoice_no = '';
                    $customerPoints->note = '';
                    $customerPoints->location = 'web';
                    $customerPoints->pre_balance = $pre_balance;
                    $customerPoints->post_balance = $post_balance;
                    $customerPoints->added_date = Carbon::now();
                    $customerPoints->valid_points = $reward;
                    $customerPoints->valid_date = $valid_date;
                    $customerPoints->valid_days = $pexpiry;
                    $customerPoints->save();

                }

                $referrer = '';

                // Fetch details if referred
                $referralRecord = DB::table('gc_referrals')
                    ->select('id', 'referrer')
                    ->where(function ($query) use ($email, $phone) {
                        $query->where('referral', $email)
                            ->orWhere('referral', $phone);
                    })
                    ->where('status', '1')
                    ->orderByDesc('referred_date')
                    ->first();

                // Fetch customer's loyalty points
                $custLoyaltyPoints = DB::table('pos_customer')
                    ->where('customer_id', $referrer)
                    ->value('loyalty_point');

                // Fetch reward for refer-a-friend
                $reward = DB::table('gc_rewards')
                    ->where('slug', 'refer-a-friend')
                    ->where('status', '1')
                    ->value('points');

                // Calculate total loyalty points for the referrer
                $referrerUpdateDate = now();
                $loyaltyPoints = (int)$custLoyaltyPoints + (int)$reward;

                // Insert new customer
                DB::table('pos_customer')->insert([
                    'date_added' => now(),
                    'loyalty_point' => $loyaltyPoints, // You need to define $loyalty_points somewhere
                    'mr_ms' => $salutation,
                    'customer_name' => strip_tags($customerName),
                    'email' => $email,
                    'phone_code' => $phoneCode,
                    'phone' => $phone,
                    'dob' => $dob,
                    'password' => $password,
                    'company' => $company,
                    'organization' => $organization,
                    'reg_flag' => '2',
                    'country' => $country,
                    'gender' => $gender,
                    'customer_id' => $customer_id,
                    'store_id' => '600',
                    'status' => 'Active',
                    'create_date' => now(),
                ]);


                if ($referralRecord) {
                    $referralId = $referralRecord->id;
                    $referrer = $referralRecord->referrer;

                    // Fetch referrer loyalty points
                    $referrerLoyaltyPoints = DB::table('pos_customer')
                        ->where('customer_id', $referrer)
                        ->value('loyalty_point');

                    // Fetch reward
                    $reward = DB::table('gc_rewards')
                        ->where('slug', 'refer-a-friend')
                        ->where('status', '1')
                        ->value('points');

                    if ($reward > 0) {
                        $referrerUpdateDate = now();
                        $loyaltyPoints = $referrerLoyaltyPoints + $reward;

                        // Update referrer loyalty points
                        DB::table('pos_customer')
                            ->where('customer_id', $referrer)
                            ->update([
                                'loyalty_point' => $loyaltyPoints,
                                'date_added' => $referrerUpdateDate,
                            ]);

                        // Insert points record
                        DB::table('gc_points')->insert([
                            'user' => $referrer,
                            'type' => 'refer-a-friend',
                            'points' => $reward,
                            'added_date' => now(),
                        ]);

                        // Add point transaction
                        $preBalance = DB::table('customer_points')
                            ->where('customer_id', $referrer)
                            ->orderBy('id', 'DESC')
                            ->value('post_balance');

                        $postBalance = $preBalance + $reward;

                        DB::table('customer_points')->insert([
                            'customer_id' => $referrer,
                            'point_in' => $reward,
                            'point_out' => 0,
                            'point_type' => 'refer-a-friend',
                            'note' => '',
                            'location' => 'web',
                            'pre_balance' => $preBalance,
                            'post_balance' => $postBalance,
                            'added_date' => now(),
                        ]);
                    }

                    // Update referral status to inactive
                    DB::table('gc_referrals')
                        ->where('id', $referralId)
                        ->update(['status' => '0']);
                }

                $customerDetails = User::where('customer_id', $customer_id)->first();
            }

            Auth::loginUsingId($customerDetails->id);

            $success_msg = "You registered with us successfully.";

//            \Mail::send('frontend.mail.registerMail', compact('success_msg'), function ($message) use ($request) {
//                $message->from('sushobhons@gmail.com');
//                $message->to('chakishreya@gmail.com', 'User')->subject('Sign Up - Gift Center');
//            });


            $result_array = ["result" => "1", "msg" => $success_msg, "result_arr" => $result_array];
        } else {
            $result_array = ["result" => "0", "msg" => $error_msg, "result_arr" => $result_array];
        }

        return response()->json($result_array);
    }


    public function loyaltyProgram()
    {
        $user = auth()->user();

        // Or using Auth facade
        // $user = Auth::user();

        //dd($user);
        $domain_id = 1; // Replace with the actual domain ID

        // Fetch article
        $article = Article::where('slug', 'loyalty-program')
            ->where('domain', $domain_id)
            ->where('status', 1)
            ->first();

        // Fetch tiers and rewards
        $tiers_arr = [];
        $rewards_title_arr = [];

        if ($article) {
            // Fetching tiers
            $tiers = Tier::where('status', '1')
                ->whereIn('show_in', ['both', 'web'])
                ->orderBy('id', 'ASC')
                ->get();

            foreach ($tiers as $tier) {
                // Fetching rewards for each tier
                $rewards = Reward::select('title AS reward_title', 'slug AS reward_slug', 'points')
                    ->where('status', '1')
                    ->whereIn('show_in', ['both', 'web'])
                    ->where('tier_id', $tier->id)
                    ->orderBy('tier_id', 'ASC')
                    ->orderBy('order_key', 'ASC')
                    ->get();

                // Create a new array to store modified values
                $tierData = $tier->toArray();
                $tierData['rewards'] = [];

                if ($rewards->count() > 0) {
                    foreach ($rewards as $reward) {
                        $tierData['rewards'][$reward->reward_slug] = $reward->points;
                        $rewards_title_arr[$reward->reward_slug] = $reward->reward_title;
                    }
                }

                $tiers_arr[$tier->id] = $tierData;
            }
        }

        // Check user session
        $total_purchase = 0;
        //$user = auth()->user();
        $user = Auth::user();
        // if (Auth::check()) {
        //     // Authentication successful
        //     $user = Auth::user();
        //     dd('Logged In');
        // } else {
        //     // Authentication failed
        //     dd('Authentication failed');
        // }
        if ($user) {
            //  $userCode = session('user_code');

            // Fetch customer record
            $customerRecord = DB::table('pos_customer')
                ->select('customer_id', 'tier_updated_date')
                ->where('customer_id', $user->customer_id)
                ->first();

            if ($customerRecord) {
                $cust_tier_updated_date = $customerRecord->tier_updated_date;

                // Fetch total purchase amount
                $totalPurchaseRecord = DB::table('sales_table_item_online')
                    ->select(DB::raw('ROUND(SUM(wholesale_price * (1 + tax / 100) * qty)) AS total_purchase_amount'), 'customer_no')
                    ->where('customer_no', $user->customer_id)
                    ->whereBetween('sale_date', [$cust_tier_updated_date, now()])
                    ->groupBy('customer_no')
                    ->first();

                if ($totalPurchaseRecord) {
                    //$total_purchase = $totalPurchaseRecord->total_purchase_amount;
                    $total_purchase = $totalPurchaseRecord->total_purchase_amount;
                }
            }
        } else {
            $total_purchase = 0;
        }
        // dd($total_purchase);

        // Pass data to the view
        return view('frontend.loyalty-program', [
            'article' => $article,
            'tiers_arr' => $tiers_arr,
            'rewards_title_arr' => $rewards_title_arr,
            'total_purchase' => $total_purchase,
        ]);
    }

    public function myAccount()
    {
        $user = Auth::user();
        if ($user) {
            $dob = $user->dob;
            $dobArray = explode("-", $dob);
            $dd = $dobArray[2];
            $mm = $dobArray[1];
            $yyyy = $dobArray[0];
            return view('frontend.pages.my-account', compact('user', 'dd', 'mm', 'yyyy'));
        } else {
            return redirect('/'); // Redirect to the home page or login page
        }

    }

    public function saveProfile(Request $request)
    {
        $error_msg = '';
        $success_msg = '';
        $result_array = [];

        $customer_id = $request->input('customer_id');
        $customer_name = $request->input('customer_name');
        $gender = $request->input('gender');
        // $company = $request->input('company');
        $email = $request->input('email');
        $phone_code = $request->input('phone_code');
        $phone = $request->input('phone');
        $country = $request->input('country');
        // $new_password = $request->input('new_password');
        //$old_password = $request->input('old_password');
        $address = $request->input('address');
        //  $street_number = $request->input('street_number');
        $street = $request->input('street');
        // $city = $request->input('city');
        // $state = $request->input('state');
        // $zip_code = $request->input('zip_code');

        // Additional logic for phone number normalization
        $ptn = "/^0/";
        $rpltxt = "";
        $phone = preg_replace($ptn, $rpltxt, $phone);

        $dd = $request->input('day', '00');
        $mm = $request->input('month', '00');
        $yyyy = $request->input('year', '0000');
        $dob = $yyyy . '-' . $mm . '-' . $dd;

        if ($email !== '') {
            $existingCustomer = DB::table('pos_customer')
                ->where('email', $email);

            if ($customer_id !== "") {
                $existingCustomer->where('customer_id', '!=', $customer_id);
            }

            $num_customer_record = $existingCustomer->count();

            if ($num_customer_record > 0) {
                $error_msg = "Email address is already registered with us";
            }
        }

        if ($phone !== '') {
            $existingPhoneCustomer = DB::table('pos_customer')
                ->where('phone', $phone);

            if ($customer_id !== "") {
                $existingPhoneCustomer->where('customer_id', '!=', $customer_id);
            }

            $num_customer_record = $existingPhoneCustomer->count();

            if ($num_customer_record > 0) {
                $error_msg = "Phone number is already registered with us";
            }
        }

        // if ($new_password !== "") {
        //     $num_pwd = DB::table('pos_customer')
        //         ->where('customer_id', $customer_id)
        //         ->where('password', md5($old_password))
        //         ->count();

        //     if ($num_pwd == 0) {
        //         $error_msg = "Old password does not match our records";
        //     }
        // }
        if ($error_msg == "") {
            $updateData = [
                'date_added' => now(),
                'customer_name' => $customer_name,
                'address' => $address,
                'dob' => $dob,
                //   'street_number' => $street_number,
                'street' => $street,
                //  'city' => $city,
                //   'state' => $state,
                // 'zip_code' => $zip_code,
                'email' => $email,
                'gender' => $gender,
                'phone' => $phone,
                'phone_code' => $phone_code,
                'country' => $country,
            ];

            // if ($new_password !== "") {
            //     $updateData['password'] = md5($new_password);
            // }

            DB::table('pos_customer')
                ->where('customer_id', $customer_id)
                ->update($updateData);
            //dd($updateData);
            $success_msg = "Your changes saved successfully";
        }


        if ($error_msg == '') {
            return response()->json(["result" => "1", "msg" => $success_msg, "result_arr" => $result_array]);
        } else {
            return response()->json(["result" => "0", "msg" => $error_msg, "result_arr" => $result_array]);
        }
    }

    public function requestProduct()
    {
        return view('frontend.pages.request-product');
    }

    public function saveRequestProduct(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(["result" => false, "message" => 'Sorry! Please sign in to request a product', "data" => null]);
        }

        $customerId = $user->customer_id;
        $comment = $request->input('comment');

        if (empty($comment)) {
            return response()->json(["result" => false, "message" => 'Please enter your request', "data" => null]);
        }

        DB::table('gc_requests')->insert([
            'customer_id' => $customerId,
            'request' => $comment,
            'added_date' => now(),
        ]);


        return response()->json(["result" => true, "message" => 'Thanks! We will contact you as soon as your order is ready', "data" => null]);

    }

    public function lovesList()
    {

        $products = [];
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $wishlistItems = Wishlist::where('customer_id', $customerId)->get();
            foreach ($wishlistItems as $wishlistItem) {
                $product = DB::table('product_table as pt')
                    ->select([
                        'wishlist.wish_id',
                        DB::raw("REPLACE(`pt`.`title`, '\\\\', '') AS `name`"),
                        DB::raw("CONCAT(IFNULL(REPLACE(`f`.`family_name`, '\\\\', ''), ''), ' ', `pt`.`fam_name`) AS `family_name`"),
                        'pt.product_id',
                        DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                        'pt.product_no',
                        'pt.photo1',
                        DB::raw("FORMAT(`pt`.`main_price`, 0) AS `main_price`"),
                    ])
                    ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
                    ->join(DB::raw("(SELECT `product_id`, `wish_id` FROM `gc_wishlist`) as `wishlist`"), 'pt.product_id', '=', 'wishlist.product_id')
                    ->where('pt.product_id', $wishlistItem->product_id)
                    ->first(); // Use first() to get a single object

                if ($product) {
                    $resImgPr = DB::table('product_more_pic_tbl')->where('product_id', $product->product_id)->whereNotNull('aws')->orderBy('id')->limit(1)->first();

                    $productImg = $product->photo1 == '' ? config('app.no_image_url') : $product->photo1;

                    if ($resImgPr) {
                        $productImg = $resImgPr->aws != '' ? $resImgPr->aws : $productImg;
                    }
                    $product->picture = $productImg;
                    $products[] = (array)$product; // Cast the object to an array
                }
            }
        }

        return view('frontend.pages.loves-list', compact('products'));
    }

}
