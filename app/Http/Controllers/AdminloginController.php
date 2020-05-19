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

class AdminloginController extends Controller
{
    public function getToken(Request $request){
        $customClaims = $this->createCustomClaims($request);

        $factory = JWTFactory::customClaims([
            'sub'   => env('APP_KEY'),
            'uuid' =>  $customClaims
        ]);

        $payload = $factory->make();
        $token = JWTAuth::encode($payload);
        return response(['token'=> "{$token}"], 200);
    }

    public function readToken(Request $request){
        if(! $token = JWTAuth::getToken()){
            return response(['message' => 'unauthenticated', 'status' => 'error'], 401);
        }
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token', 'status' => 'error'], 401);
        }
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
        $currDate = time();
        $expiry = strtotime($uuid->expiry);
        if($currDate > $expiry){
            return response(['message' => 'expired token'], 400);
        }
        return response(['data' => $apy], 200); 
    }

    private function createCustomClaims($request){
        date_default_timezone_set('Africa/Lagos');
        $now = Carbon::now();

        $customClaims = $request->all();
        $customClaims['now'] = $now->format('Y-m-d H:i:s');
        $customClaims['expiry'] = $now->addHour(6)->format('Y-m-d H:i:s');
        return $customClaims;
    }

    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }
}
