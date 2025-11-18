<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }
        $agree = 'nullable';
        if (gs('agree')) {
            $agree = 'required';
        }

        $validate = Validator::make($data, [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|string|email|unique:users',
            'password' => ['required', 'confirmed', $passwordValidation],
            'agree' => $agree
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required'
        ]);

        return $validate;
    }


    public function register(Request $request)
    {
        if (!gs('registration')) {
            $notify[] = 'Registration not allowed';
            return apiResponse("registration_disabled", "error", $notify);
        }

        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $emailExists = User::where('email', $request->email)->exists();

        if ($emailExists) {
            $notify[] = 'Email already exists';
            return apiResponse('email_exists', 'error', $notify);
        }

        $user = $this->create($request->all());

        sendEmailVerification($user);

        $data['access_token'] = $user->createToken('auth_token')->plainTextToken;
        $data['user'] = $user;
        $data['token_type'] = 'Bearer';
        $notify[] = 'Registration successful, please check your email for token to complete registration';

        return apiResponse("registration_success", "success", $notify, $data);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        // 2. Process referral if exists
        $referBy = $data['reference'] ?? null;
        $referUser = $referBy ? User::where('username', $referBy)->first() : null;

        // 3. Generate unique verification code (as string)
        do {
            $ver_code = (string) mt_rand(100000, 999999);
        } while (User::where('ver_code', $ver_code)->exists());

       

        // 4. Prepare user data
        $user = new User();
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->email = strtolower($data['email']);
        $user->password = Hash::make($data['password']);
        $user->ref_by = $referUser ? $referUser->id : 0;
        $user->ev = Status::UNVERIFIED;
        $user->ver_code = $ver_code;
        $user->sv = gs('sv') ? Status::UNVERIFIED : Status::VERIFIED;
        $user->ts = Status::DISABLE;
        $user->tv = Status::VERIFIED;

        // 5. Database transaction
        DB::beginTransaction();

        try {
            // Save user
            if (!$user->save()) {
                throw new \Exception("User save failed");
            }

            // Get fresh instance
            $freshUser = User::find($user->id);

           
            if ($freshUser->ver_code !== $ver_code) {
                throw new \Exception("Verification code mismatch! Generated: {$ver_code}, Stored: {$freshUser->ver_code}");
            }


            // Create admin notification
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'New member registered';
            $adminNotification->click_url = urlPath('admin.user.detail', $user->id);
            $adminNotification->save();

            // Create login log
            $ip = getRealIP();
            $userLogin = new UserLogin();

            if ($existingLogin = UserLogin::where('user_ip', $ip)->first()) {
                $userLogin->longitude = $existingLogin->longitude;
                $userLogin->latitude = $existingLogin->latitude;
                $userLogin->city = $existingLogin->city;
                $userLogin->country_code = $existingLogin->country_code;
                $userLogin->country = $existingLogin->country;
            } else {
                $info = json_decode(json_encode(getIpInfo()), true);
                $userLogin->longitude = @implode(',', $info['long']);
                $userLogin->latitude = @implode(',', $info['lat']);
                $userLogin->city = @implode(',', $info['city']);
                $userLogin->country_code = @implode(',', $info['code']);
                $userLogin->country = @implode(',', $info['country']);
            }

            $userAgent = osBrowser();
            $userLogin->user_id = $user->id;
            $userLogin->user_ip = $ip;
            $userLogin->browser = $userAgent['browser'] ?? null;
            $userLogin->os = $userAgent['os_platform'] ?? null;
            $userLogin->save();

            DB::commit();

            // Return response with verification audit
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error("Registration failed: " . $e->getMessage(), [
            //     'exception' => $e,
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'verification_code' => $ver_code // For debugging
            ], 500);
        }
    }

   
}
