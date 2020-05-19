<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function checkToken(){
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                    return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

                return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                return response()->json(['token_invalid'], $e->getStatusCode());

        }
        return response()->json(compact('user'));

    }  

    public function register(Request $request){
        $this->validateObj;

        $user = User::create([
            'auth_id' => $request->get('auth_id'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'firstname' => $request->get('firstname'),
            'lastname' => $request->get('lastname'),
            'staff_id' => $request->get('staff_id'),
            'department' => $request->get('department'),
            'position' => $request->get('position'),
            'waveid' => $request->get('waveid'),
            'girotoken' => $request->get('girotoken'),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
    }

    private function validateObj(){
        $validator = Validator::make($request->all(), [
            'auth_id' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'firstname' => 'required',
            'lastname' => 'required',
            'staff_id' => 'required',
            'department' => 'required',
            'position' => 'required',
            'waveid' => 'required',
            'girotoken' => 'required'
        ]);

            if($validator->fails()){
                return response()->json($validator->errors()->toJson(), 400);
            }

    }

    public function getAuthenticatedUser()
    {
        try {

                if (! $user = JWTAuth::parseToken()->authenticate()) {
                        return response()->json(['user_not_found'], 404);
                }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

                return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

                return response()->json(['token_absent'], $e->getStatusCode());

        }

            return response()->json(compact('user'));
    }

    public function authenticate(Request $request){
            $credentials = $request->all();

            try {
                if (! $token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'invalid_credentials'], 400);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token'], 500);
            }

            return response()->json(compact('token'));
    }

            
}