<?php

namespace App\Http\Middleware;

use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Adminlogin;

use Closure;

class Adminmiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //check for token
        if(! $token = JWTAuth::getToken()){
            return response(['message' => 'Unauthorized access', 'status'=>'error'], 401);
        }
        // check if expired
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token', 'status' => 'error'], 401);
        }
        // get tokens payload
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
        
        //check matching record in database
        if(!Adminlogin::where('authid', $uuid->authid)->where('password',$uuid->password)->exists()){
            return response(['message' => 'unauthenticated', 'status'=>'error'], 401);
        }
        
        return $next($request);
    }

    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }
}
