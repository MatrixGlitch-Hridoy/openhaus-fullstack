<?php

namespace App\Http\Controllers;

use App\Handlers\AuthHandler;
use App\Helpers\PublicHelper;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // register
    public function register(Request $request)
    {
        $input = $request->only('name', 'email', 'password', 'c_password');

        $validator = Validator::make($input, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            // return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        if ($user) {
            $authHandler = new AuthHandler;
            $token = $authHandler->GenerateToken($user);

            $success = [
                'user' => $user,
                'token' => $token,
            ];

            // return $this->sendResponse($success, 'user registered successfully', 201);
        }
    }

    public function login(Request $request)
    {
        $input = $request->only('email', 'password');

        $validator = Validator::make($input, [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            // return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $remember = $request->remember;

        if (Auth::attempt($input, $remember)) {
            $user = Auth::user();

            $authHandler = new AuthHandler;
            $token = $authHandler->GenerateToken($user);

            $success = ['user' => $user, 'token' => $token];
            return response()->json($success);

            // return $this->sendResponse($success, 'Logged In');
        } else {
            return $this->sendError('Unauthorized', ['error' => "Invalid Login credentials"], 401);
        }
    }
    public function logout(Request $request)
    {

        $publicHelper = new PublicHelper();
        // Get the user's token from the request
        // $token = $request->bearerToken();


        try {
            // Decode the token to get the token ID (jti)
            $secretKey  = env('JWT_KEY');
            // $decodedToken = JWT::decode($token, new Key($secretKey, 'HS512'));
            $decodedToken = $publicHelper->GetAndDecodeJWT();
            // Add the token to the blacklist with an expiration time to automatically remove it
            Redis::setex("blacklist:$decodedToken->jti", 3600, 'true'); // 1 hour expiration

            // Clear the token cookie
            // $cookie = Cookie::forget('token');
            return response()->json(['message' => 'Logged out successfully']);
            // ->withCookie($cookie);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }


        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
