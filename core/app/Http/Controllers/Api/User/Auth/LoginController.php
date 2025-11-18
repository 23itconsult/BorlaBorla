<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\SocialLogin;
use App\Models\UserLogin;
use App\Models\User;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Apple\Provider as AppleProvider;
use App\Services\AppleClientSecret;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

  private $guard;
    private $platform;
    public function __construct($guard = 'user')
    {
        $this->username = $this->findUsername();
        $this->guard = $guard;
    }

    

    public function redirectToApple()
    {
        //  return "hello";
        return Socialite::driver('apple')->redirect();
    }





    public function handleAppleCallback()
    {
        try {

            // return "hello";
            $appleUser = Socialite::driver('apple')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $appleUser->getEmail()],
                ['name' => $appleUser->getName() ?? 'Apple User']
            );

            Auth::login($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in with Apple',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Apple Login Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

      public function apple_auth()
       {
      $config = config('services.apple');
        $clientId = $config['client_id'];
        $redirectUri = $config['redirect'];
        $state = bin2hex(random_bytes(16)); // Generate a random state for security

        // Build the Apple authorization URL
        $url = 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'response_mode' => 'form_post',
            'scope' => 'name email',
            'state' => $state,
        ]);

        return response()->json(['url' => $url, 'state' => $state]);
        
       }

   public function apple_callback(Request $request)
{
    try {
        
          config([
            'services.apple.client_secret' => AppleClientSecret::generate()
        ]);

        $appleUser = Socialite::driver('apple')->stateless()->user();
         $Social_login=app(SocialLogin::class);
        
        if ($this->guard == "user") {
            $userData = User::where('provider_id', $appleUser->id)->first();
        } else {
            $userData = Collector::where('provider_id', $appleUser->id)->first();
        }

        if (!$userData) {
            if ($this->guard == "user") {
                $emailExists = User::where('email', $appleUser->email)->exists();
            } else {
                $emailExists = Collector::where('email', $appleUser->email)->exists();
            }

            if ($emailExists) {
                $notify[] = 'Email already exists';
                return apiResponse('email_exists', 'error', $notify);
            }
            
            $user = (object)[
            'id' => $appleUser->id,
            'firstname' => $appleUser->first_name ?? 'Unknown',
            'lastname' => $appleUser->last_name ?? 'Unknown',
            'email'=>$appleUser->email
            ];

            $userData =  $Social_login->createUser($user, $appleUser->id);
        }


        if ($this->guard == "user") {
            $tokenResult = $userData->createToken('auth_token')->plainTextToken;
            $userKeyName = "user";
        } else {
            $tokenResult = $userData->createToken('collector_token')->plainTextToken;
            $userKeyName = "collector";
        }
        
       

        $logs=$Social_login->loginLog($userData);

        $response[] = 'Login Successful';
        return apiResponse("login_success", "success", $response, [
            $userKeyName => $userData,
            'access_token' => $tokenResult,
            'token_type' => 'Bearer'
        ]);

        // $user = User::updateOrCreate(
        //     ['email' => $appleUser->email],
        //     [
        //         'firstname' => $appleUser->firstName ?? 'Unknown',
        //          'lastname' => $appleUser->lastName ?? 'Unknown',
        //          'email' => $appleUser->email,
        //         'apple_id' => $appleUser->id,
        //         'password'=>$appleUser->id,
        //         'updated_at' => now(),
        //     ]
        // );

        // // Generate an API token (e.g., Laravel Sanctum)
        //  $user->save();
        // $token = $user->createToken('apple-auth')->plainTextToken;

        // return response()->json(['token' => $token, 'user' => $user]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Apple login failed: ' . $e->getMessage()], 400);
    }
}
    public function login(Request $request)
    {
        $validator = $this->validateLogin($request);
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $credentials = request([$this->username, 'password']);

        if (!Auth::attempt(array_merge($credentials, ['is_deleted' => Status::NO]))) {
            $response[] = 'The provided credentials can not match our record';
            return apiResponse("invalid_credential", "error", $response);
        }

        $user        = $request->user();
        $tokenResult = $user->createToken('auth_token', ['user'])->plainTextToken;
        $this->authenticated($request, $user);
        $response[] = 'Login Successful';

        return apiResponse("login_success", "success", $response, [
            'user'         => $user,
            'access_token' => $tokenResult,
            'token_type'   => 'Bearer'
        ]);
    }

    public function findUsername()
    {
        $login     = request()->input('username');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$fieldType => $login]);
        return $fieldType;
    }

    public function username()
    {
        return $this->username;
    }

    protected function validateLogin(Request $request):object
    {
        $validationRule = [
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ];
        $validate = Validator::make($request->all(), $validationRule);
        return $validate;
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        $notify[] = 'Logout Successful';
        return apiResponse("logout", "success", $notify);
    }

    public function authenticated(Request $request, $user)
    {
        $user->tv = $user->ts == Status::VERIFIED ? Status::UNVERIFIED : Status::VERIFIED;
        $user->save();
        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();
        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();
    }

    public function checkToken(Request $request)
    {
        $validationRule = [
            'token' => 'required',
        ];

        $validator = Validator::make($request->all(), $validationRule);
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }
        $accessToken = PersonalAccessToken::findToken($request->token);
        if ($accessToken) {
            $notify[]      = 'Token exists';
            $data['token'] = $request->token;
            return apiResponse("token_exists", "success", $notify, $data);
        }

        $notify[] = 'Token doesn\'t exists';

        return apiResponse("token_not_exists", "error", $notify);
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,facebook,linkedin,apple',
            // 'token'    => 'required',
            // 'platform' => 'required|in:ios,android',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $socialLogin = new SocialLogin();
        return $socialLogin->login($request->input('provider'));
    }
}
