<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\Collector;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Config;

class SocialLogin
{
    private $guard;
    private $platform;

    public function __construct($guard = 'user')
    {
        $this->guard = $guard;
        $this->platform = request()->platform ?? 'android';
        $config = $this->getGoogleConfigForGuardAndPlatform($guard, $this->platform);
        Config::set('services.google', $config);
    }

    protected function getGoogleConfigForGuardAndPlatform($guard, $platform)
    {
        if ($guard === 'collector') {
            return [
                'client_id' => $platform === 'ios' ? env('CLIENT_IOS_COLLECTOR_ID') : env('CLIENT_ANDROID_COLLECTOR_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => "#",
            ];
        }

        return [
            'client_id' => $platform === 'ios' ? env('CLIENT_IOS_USER_ID') : env('CLIENT_ANDROID_USER_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => "#",
        ];
    }

    public function login()
    {
        if ($this->guard == "user") {
            if (!gs('registration')) {
                $notify[] = 'New account registration is currently disabled';
                return apiResponse('restricted', 'error', $notify);
            }
        } else {
            if (!gs('driver_registration')) {
                $notify[] = 'New account registration is currently disabled';
                return apiResponse('restricted', 'error', $notify);
            }
        }

        $provider=='google';
        $driver = Socialite::driver($provider);
   

        try {
            $user = (object) $driver->userFromToken(request()->token)->user;
        } catch (\Throwable $th) {
            \Log::error('Google Login Error: ' . $th->getMessage());
            $notify[] = "Something went wrong";
            return apiResponse('exception', 'error', $notify);
        }

        if ($this->guard == "user") {
            $userData = User::where('provider_id', $user->id)->first();
        } else {
            $userData = Collector::where('provider_id', $user->id)->first();
        }

        if (!$userData) {
            if ($this->guard == "user") {
                $emailExists = User::where('email', @$user->email)->exists();
            } else {
                $emailExists = Collector::where('email', @$user->email)->exists();
            }

            if ($emailExists) {
                $notify[] = 'Email already exists';
                return apiResponse('email_exists', 'error', $notify);
            }
            $userData = $this->createUser($user, $provider);
        }


        if ($this->guard == "user") {
            $tokenResult = $userData->createToken('auth_token')->plainTextToken;
            $userKeyName = "user";
        } else {
            $tokenResult = $userData->createToken('collector_token')->plainTextToken;
            $userKeyName = "collector";
        }

        $this->loginLog($userData);

        $response[] = 'Login Successful';
        return apiResponse("login_success", "success", $response, [
            $userKeyName => $userData,
            'access_token' => $tokenResult,
            'token_type' => 'Bearer'
        ]);
    }

    public function createUser($user, $provider)
    {
        $password = getTrx(8);

        $firstName = null;
        $lastName = null;

        if (@$user->first_name) {
            $firstName = $user->first_name;
        }
        if (@$user->last_name) {
            $lastName = $user->last_name;
        }

        if ((!$firstName || !$lastName) && @$user->name) {
            $firstName = preg_replace('/\W\w+\s*(\W*)$/', '$1', $user->name);
            $pieces = explode(' ', $user->name);
            $lastName = array_pop($pieces);
        }


        if ($this->guard == "user") {
            $newUser = new User();
        } else {
            $newUser = new Collector();
        }

        $newUser->provider_id = $user->id;
        $newUser->email = $user->email;

        $newUser->password = Hash::make($password);
        $newUser->firstname = $firstName;
        $newUser->lastname = $lastName;

        $newUser->status = Status::VERIFIED;
        $newUser->ev = Status::VERIFIED;
        $newUser->sv = gs('sv') ? Status::UNVERIFIED : Status::VERIFIED;
        $newUser->ts = Status::DISABLE;
        $newUser->tv = Status::VERIFIED;
        $newUser->provider = $provider;
        $newUser->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $newUser->id;

        if ($this->guard == "user") {
            $adminNotification->title = 'New User registered';
            $adminNotification->click_url = urlPath('admin.user.detail', $newUser->id);
            $user = User::find($newUser->id);
        } else {
            $adminNotification->title = 'New Waste Collector registered';
            $adminNotification->click_url = urlPath('admin.collector.detail', $newUser->id);
            $user = Collector::find($newUser->id);
        }
        $adminNotification->save();


        return $user;
    }

    public function loginLog($user)
    {

        //Login Log Create
        $ip = getRealIP();
        if ($this->guard == "user") {
            $exist = UserLogin::where('user_ip', $ip)->where('user_id', $user->id)->first();
        } else {
            $exist = UserLogin::where('user_ip', $ip)->where('collector_id', $user->id)->first();
        }

        $userLogin = new UserLogin();


        //Check exist or not
        if ($exist) {
            $userLogin->longitude = $exist->longitude;
            $userLogin->latitude = $exist->latitude;
            $userLogin->city = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country = $exist->country;
        } else {
            $info = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude = @implode(',', $info['long']);
            $userLogin->latitude = @implode(',', $info['lat']);
            $userLogin->city = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country = @implode(',', $info['country']);
        }

        $userAgent = osBrowser();
        if ($this->guard == "user") {
            $userLogin->user_id = $user->id;
        } else {
            $userLogin->collector_id = $user->id;
        }

        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save();
    }
}
