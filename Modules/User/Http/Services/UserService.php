<?php

namespace Modules\User\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Modules\User\Entities\User;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class UserService
{
    protected User $user;

    /**
     * Save a new Customer
     *
     * @param array $requestData
     * @return array
     */
    public function register(array $requestData): array
    {

        $emailArray = explode("@", $requestData['email']);
        $userName = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $emailArray[0]));
        $domainArray = explode(".", $emailArray[1]);
        $userName = $userName . $domainArray[0];
        $count = User::where('customer_name', $userName)->count();
        if ($count > 0) {
            $userName = $userName . $count;
        }
        $requestData["customer_name"] = $userName;
        $requestData["phone_code"] = '962';
        $requestData["store_id"] = '600';

        $customer_id = $this->generateCustomerId();
        $requestData["customer_id"] = $customer_id;
        $requestData["password"] = md5($requestData['password']);
        $user = new User();
        $user->fill($requestData);
        $user->save();

        return [
            'res' => true,
            'msg' => '',
            'data' => $user
        ];
    }

// Function to generate a custom customer_id
    private function generateCustomerId()
    {

        $maxSequence = User::where('customer_id', 'like', '%GC%')
            ->max(DB::raw('CONVERT(SUBSTRING_INDEX(customer_id, "-", -1), UNSIGNED INTEGER)'));


        $nextSequence = $maxSequence + 1;


        if ($nextSequence === null) {
            $nextSequence = 100;
        }

        // Create the customer_id
        $customer_id = 'GC-' . $nextSequence;

        return $customer_id;
    }


    /**
     * Sign in  by user.
     *
     * @param LoginUserRequest $request
     * @return JsonResponse
     */

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();


        $user = User::where('email', $validatedData['email'])->first();

        if (!$user || !Hash::check($validatedData['password'], $user->password)) {

            return response()->json(['message' => 'Invalid credentials'], 401);
        }


        $token = $user->createToken('auth_token')->plainTextToken;


        $response = [
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful',
        ];

        return response()->json($response);
    }

    public function saveOTP($email = null, $phoneCode = null, $phone = null, $country = null, $otp)
    {
        // TODO: Implement saving OTP and other details to the database
        // Example: Save the details to the 'gc_register' table
        $email = $email ?? '';
        $phoneCode = $phoneCode ?? '';
        $phone = $phone ?? '';
        $country = $country ?? '';

        $sql = "INSERT INTO `gc_register` 
                        (`date_added`, `country`, `email`, `phone_code`, `phone`, `otp`) 
                        VALUES 
                        (NOW(), :country, :email, :phone_code, :phone, :otp)";

        // Use your database connection to execute the query with parameters
        // Example using Laravel Query Builder
        $result = \DB::insert($sql, [
            'country' => $country,
            'email' => $email,
            'phone_code' => $phoneCode,
            'phone' => $phone,
            'otp' => $otp,
        ]);

        return $result;
    }

    public function sendOTP($email, $phoneCode, $phone, $otp, $type)
    {
        if ($type == 'email') {
            return $this->sendOTPviaEmail($email, $otp);
        } else {
            return $this->sendOTPviaSMS($phoneCode, $phone, $otp);
        }

    }

    private function sendOTPviaEmail($email,$otp)
    {
        $content = '<#> ' . $otp . ' is your verification code from Gifts Center for registration. Please do not share it with anyone.';
        Helper::sendMail($email, 'User', 'OTP For Login', $content);
    }

    private function sendMails($email, $subject, $content)
    {

        try {
//            Mail::send([], [], function ($message) use ($email, $subject, $content) {
//                $message->to($email)
//                    ->from('no-reply@giftscenter.com', 'Gifts Center')
//                    ->subject($subject)
//                    ->setBody($content, 'text/html');
//            });
//            Mail::raw($content, function ($message) use ($email, $subject) {
//                $message->to($email)
//                    ->subject($subject);
//            });
            Mail::html($content, function ($message) use ($email, $subject) {
                $message
                ->to($email)
                ->subject($subject);
            });

            return ['status' => true, 'message' => 'Mail sent successfully.'];
        } catch (\Exception $e) {
            dd($e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function sendMail($email, $subject, $content)
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug  = 0;
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port       = config('mail.mailers.smtp.port');

            //Recipients
            $mail->addAddress($email, 'AJ');
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;

            if( !$mail->send() ) {
                return back()->with("failed", "Email not sent.")->withErrors($mail->ErrorInfo);
            }

            else {
                return back()->with("success", "Email has been sent.");
            }
        } catch (Exception $e) {
            throw new Exception('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        }
    }

//    private function sendOTPviaSMS($phoneCode, $phone, $otp)
//    {
//        $msg = urlencode($otp . " is your verification code for registration. Please do not share it with anyone.");
//        $fullPhoneNumber = $phoneCode . $phone;
//
//        $smsGatewayUrl = "http://josmsservice.com/smsonline/smppinterform.cfm?numbers=" . $fullPhoneNumber . ",&senderid=GiftsCenter&AccName=giftce&AccPass=Tul7uA5KVC5nRo32&msg=" . $msg . "&requesttimeout=5000000";
//        // Get cURL resource
//        $curl = curl_init();
//
//        // Set cURL options
//        curl_setopt_array($curl, array(
//            CURLOPT_RETURNTRANSFER => 1,
//            CURLOPT_URL => $smsGatewayUrl,
//        ));
//
//        // Execute cURL request
//        $response = curl_exec($curl);
//
//        // Get HTTP response code
//        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//
//        // Close cURL resource
//        curl_close($curl);
//
//        // Check if OTP is sent successfully (modify this based on your SMS gateway response)
//        return $httpCode === 200;
//    }


    public function verifyOTP($email = null, $phoneCode = null, $phone = null, $otp)
    {

        $email = $email ?? '';
        $phoneCode = $phoneCode ?? '';
        $phone = $phone ?? '';
        // $country = $country ?? '';


        // Fetch user data based on the provided parameters
        $user = DB::table('gc_register')
            ->where('email', trim($email))  // Trim whitespaces
            ->where('phone_code', trim($phoneCode))
            ->where('phone', trim($phone))
            ->first();

        // dd(DB::getQueryLog());
        //  dd($user);
        // Check if the user exists
        if ($user) {
            // Check if the stored OTP matches the provided OTP
            if ($user->otp == $otp) {
                // OTP is valid
                return true;
            } else {
                // Invalid OTP
                return false;
            }
        } else {
            // User not found
            return false;
        }
    }


    public function registerUser($email, $phoneCode, $phone, $country)
    {
        // Check if the user with the provided email or phone already exists
        $existingUser = DB::table('gc_register')
            ->where('email', $email)
            ->orWhere(function ($query) use ($phoneCode, $phone) {
                $query->where('phone_code', $phoneCode)->where('phone', $phone);
            })
            ->first();

        if ($existingUser) {
            // User with the same email or phone already exists
            return null;
        }

        // User doesn't exist, proceed with registration
        $userId = DB::table('gc_register')->insertGetId([
            'date_added' => now(),
            'country' => $country,
            'email' => $email,
            'phone_code' => $phoneCode,
            'phone' => $phone,
            // Add other fields as needed
        ]);

        return $userId;
    }
}
