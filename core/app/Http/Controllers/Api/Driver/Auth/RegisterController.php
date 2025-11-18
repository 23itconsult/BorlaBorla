<?php

namespace App\Http\Controllers\Api\Driver\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Collector;
use App\Models\UserLogin;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
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
            'email' => 'required|string|email|unique:drivers',
            'password' => ['required', 'confirmed', $passwordValidation],
            'agree' => $agree,
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required'
        ]);

        return $validate;
    }


    public function register(Request $request)
    {
        if (!gs('driver_registration')) {
            $notify[] = 'Registration not allowed';
            return apiResponse("registration_disabled", "error", $notify);
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $emailExists = Collector::where('email', $request->email)->exists();

        if ($emailExists) {
            $notify[] = 'Email already exists';
            return apiResponse('email_exists', 'error', $notify);
        }

        $driver = $this->create($request->all());

        sendEmailVerification($driver);

        $data['access_token'] = $driver->createToken('collector_token')->plainTextToken;
        $data['collector'] = $driver;
        $data['token_type'] = 'Bearer';
        $data['image_path'] = getFilePath('driver');
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
        do {
            $ver_code = mt_rand(100000, 999999);
        } while (Collector::where('ver_code', $ver_code)->exists());

        $driver = new Collector();
        $email = strtolower($data['email']);
        $driver->firstname = $data['firstname'];
        $driver->lastname = $data['lastname'];
        $driver->email = $email;
        $driver->ver_code = $ver_code;
        $driver->ev = Status::UNVERIFIED;
        $driver->password = Hash::make($data['password']);
        $driver->ev = Status::UNVERIFIED;
        $driver->sv = gs('sv') ? Status::UNVERIFIED : Status::VERIFIED;
        $driver->ts = Status::DISABLE;
        $driver->tv = Status::VERIFIED;

        DB::beginTransaction();

        try {

            if (!$driver->save()) {
                throw new \Exception("User save failed");
            }

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = 0;
            $adminNotification->collector_id = $driver->id;
            $adminNotification->title = 'New Waste Collector registered';
            $adminNotification->click_url = urlPath('admin.collector.detail', $driver->id);
            $adminNotification->save();


            //Login Log Create
            $ip = getRealIP();
            $exist = UserLogin::where('user_ip', $ip)->where('collector_id', $driver->id)->first();
            $driverLogin = new UserLogin();

            $driverAgent = osBrowser();
            $driverLogin->collector_id = $driver->id;
            $driverLogin->user_ip = $ip;

            $driverLogin->browser = @$driverAgent['browser'];
            $driverLogin->os = @$driverAgent['os_platform'];
            $driverLogin->save();

            DB::commit();

            $driver = Collector::find($driver->id);
            return $driver;

        } catch (\Exception $e) {
            DB::rollBack();
            // \\Log::error("Registration failed: " . $e->getMessage(), [
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
