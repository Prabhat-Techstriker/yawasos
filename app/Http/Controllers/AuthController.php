<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use Twilio\Rest\Client;
use Facebook\Facebook;
use App\Notifications\SignupActivate;
use Illuminate\Support\Str;

class AuthController extends Controller {

	/*public function register(Request $request) {
		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'email' => 'required|string|email|unique:users',
			'password' => 'required|between:8,15|string|confirmed',
			'phone_number' => 'required|string|unique:users',
			'latitude' => 'required|numeric', 
			'longitude' => 'required|numeric',
			'device_token' => 'required|string|unique:users',
			'image_path' => 'string', 
			'message' => 'string' 
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'error' => $validator->errors(), 'message' => 'Validation failed'], 422);
		}

        if($this->verifyPhone($request->phone_number)) { //send OTP
        	$user = new User([
	            'name' => $request->name,
	            'email' => $request->email,
	            'password' => bcrypt($request->password),
	            'phone_number' => $request->phone_number,
	            'latitude' => $request->latitude,
	            'longitude' => $request->longitude,
	            'device_token' => $request->device_token,
	            'image_path' => $request->image_path,
	            'message' => $request->message,
        	]);
        	$user->save();
        	return response()->json(['success' => true, 'user' => $user, 'message' => 'Registration has been done successful and OTP sent!!.'], 201);
        } else { //Failed to send OTP
        	return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 401);
        }
    }*/


    public function register(Request $request) {
		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'email' => 'required|string|email|unique:users',
			'password' => 'required|between:8,15|string|confirmed',
			'latitude' => 'required|numeric', 
			'longitude' => 'required|numeric',
			'device_token' => 'required|string|unique:users',
			'image_path' => 'string', 
			'message' => 'string' 
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'error' => $validator->errors(), 'message' => 'Validation failed'], 422);
		}

        //if($this->verifyPhone($request->phone_number)) { //send OTP
        	$user = new User([
	            'name' => $request->name,
	            'email' => $request->email,
	            'password' => bcrypt($request->password),
	            'phone_number' => $request->phone_number,
	            'latitude' => $request->latitude,
	            'longitude' => $request->longitude,
	            'device_token' => $request->device_token,
	            'image_path' => $request->image_path,
	            'message' => $request->message,
	            'activation_token' => Str::random(60),
        	]);
        	$user->save();
        	//$user->notify(new SignupActivate($user));
        	return response()->json(['success' => true, 'user' => $user, 'message' => 'Thanks for signup! Please before you begin, you must confirm your account!'], 201);
       /* } else { //Failed to send OTP
        	return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 401);
        }*/
    }

	public function signupActivate($token){
		$user = User::where('activation_token', $token)->first();
		if (!$user) {
			$user = 'This activation token is invalid.';
			return view('Auth.active', compact('user'));
		}
		$user->active = true;
		$user->activation_token = '';
		$user->save();
		return view('Auth.active', compact('user'));
	}


	public function activateUser(Request $request){
		try {

			$userData = $request->user();
			$user = User::where('id', $userData->id)->first();
			$user->active = $request->flag;
			$user->save();

			if ($request->flag == 1) {
				return response()->json(['success' => true, 'user' => $user, 'message' => 'User activated Successfully!'], 201);
			} else {
				return response()->json(['success' => true, 'user' => $user, 'message' => 'User inactive Successfully!'], 201);
			}
		} catch (Exception $e) {
			return response()->json(['success' => false, 'message' => 'Failed to update active/inactive user.'], 401);
		}
	}


    public function verifyPhone($phoneNumber) { //verifying phone with twilio
		$token = getenv("TWILIO_AUTH_TOKEN");
		$twilio_sid = getenv("TWILIO_SID");
		$twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
		$twilio = new Client($twilio_sid, $token);

		try {
			$twilio->verify->v2->services($twilio_verify_sid)->verifications->create($phoneNumber, "sms");
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function generateOtp(Request $request) {
		$validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

		if ($validator->fails()) {
		   return response()->json($validator->errors(), 422);
		}

		$user = User::where('phone_number', $request->phone_number)->first();
		if(empty($user)) {
			return response()->json(['success' => false, 'message' => 'User not found.'], 400);
		}

		$token = getenv("TWILIO_AUTH_TOKEN");
		$twilio_sid = getenv("TWILIO_SID");
		$twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
		$twilio = new Client($twilio_sid, $token);

		try {
			$twilio->verify->v2->services($twilio_verify_sid)->verifications->create($request->phone_number, "sms");
			return response()->json(['success' => true, 'message' => 'OTP sent!!'], 200);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 401);
		}
	}

	public function loginOtp(Request $request) {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'verification_code' => 'required|numeric|digits:6',
        ]);

		if ($validator->fails()) {
		   return response()->json($validator->errors(), 422);
		}

		$token = getenv("TWILIO_AUTH_TOKEN");
		$twilio_sid = getenv("TWILIO_SID");
		$twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
		$twilio = new Client($twilio_sid, $token);

		try {
			$verification = $twilio->verify->v2->services($twilio_verify_sid)->verificationChecks->create($request->verification_code, array('to' => $request->phone_number));
			if ($verification->valid) {
				$user = tap(User::where('phone_number', $request->phone_number))->update(['phone_verified' => true]);
				$user = User::where('phone_number', $request->phone_number)->first();
				if(empty($user)) {
					return response()->json(['success' => false, 'message' => 'User not found.'], 400);
				}
				$tokenResult = $user->createToken('Personal Access Token');
				$token = $tokenResult->token;
				$token->expires_at = Carbon::now()->addWeeks(4);
				$token->save();

				return response()->json([
					'success' => true,
					'access_token' => $tokenResult->accessToken,
					'token_type' => 'Bearer',
					'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
					'phone_number' => $request['phone_number'],
					'message' => 'Logged In Successfully'
				],200);
			}
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Invalid verification code entered.'], 400);
		}
    }

    /*public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|between:8,15',
        ]);

		if ($validator->fails()) {
		   return response()->json($validator->errors(), 422);
		}

        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Wrong credentials.'], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(4);
        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ]);
    }*/

	public function login(Request $request){
		$request->validate([
		    'email' => 'required|string|email',
		    'password' => 'required|string',
		    'device_token' => 'required'
		]);
		$credentials = request(['email', 'password']);
		$credentials['active'] = 1;
		$credentials['deleted_at'] = null;
		//print_r($credentials);
		if(!Auth::attempt($credentials))
		    return response()->json([
		        'message' => 'Please before you begin, you must confirm your account!'
		    ], 401);
		$user = $request->user();
		$tokenResult = $user->createToken('Personal Access Token');
		$token = $tokenResult->token;
		if ($request->remember_me)
		    $token->expires_at = Carbon::now()->addWeeks(1);
		$token->save();
		$user = User::where('email', $request->email)->first();
		$user['device_token'] = $request->device_token;
		$user->save();
		return response()->json([
		    'access_token' => $tokenResult->accessToken,
		    'token_type'   => 'Bearer',
		    'expires_at'   => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
		    'user'         => $user
		]);
	}

	public function saveResetPassword(Request $request)
	{
		$request->validate([
			'password' => 'required|string|confirmed',   
		]);

		try{
			$passwordReset = PasswordReset::where([
				['email', $request->email]
			])->first();

			if (!$passwordReset)
				return response()->json([
					'message' => 'We cant find a user with that e-mail address.'
				], 404);
			$user->password = bcrypt($request->password);
			$user->save();
			return response()->json(['success' => true, 'message' => 'Password updated Successfully.'], 200);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Failed to update password.'], 401);
		}
	}

	public function fbLogin(Request $request, Facebook $fb) {
		$validator = Validator::make($request->all(), [
			'fb_access_token' => 'required|string'
		]);

		if ($validator->fails()) {
			return response()->json($validator->errors(), 422);
		}

		try {
			$fbResponse = $fb->get('/me?fields=id,name,email', $request->fb_access_token)->getGraphUser();
			$user = User::where('email', $fbResponse->getField('email'))->first();
			if(empty($user)) {
				$user = new User([
					'name' => $fbResponse->getField('name'),
					'email' => $fbResponse->getField('email'),
					'password' => bcrypt(rand(1,10000)),
					'phone_number' => mt_rand(1000000000, 9999999999)
				]);
				$user->save();
			}

			$tokenResult = $user->createToken('Personal Access Token');
			$token = $tokenResult->token;
			$token->expires_at = Carbon::now()->addWeeks(4);
			$token->save();

			return response()->json([
				'success' => true,
				'access_token' => $tokenResult->accessToken,
				'token_type' => 'Bearer',
				'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
				'message' => 'Logged In Successfully'
			],200);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Unable to login, please try again.'], 401);
		}
	}

	public function logout(Request $request) {
		$accessToken = Auth::user()->token();
		$user = User::where('id', $accessToken->user_id)->first();
		$user['device_token'] = null;
		$user->save();
		$request->user()->token()->revoke();
		return response()->json(['success' => true, 'message' => 'Successfully logged out'], 200);
	}

	public function me() {
		return response()->json(['success' => true, 'my_profile'=> Auth::user(), 'message' => 'Succeffully fetched my profile data.'], 200);
	}
}
