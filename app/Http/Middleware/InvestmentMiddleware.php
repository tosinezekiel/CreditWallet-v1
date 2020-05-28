<?php

namespace App\Http\Middleware;

use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Investment;

use Closure;

class InvestmentMiddleware
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
            return response(['message' => 'unauthorized access', 'status'=>'error'], 401);
        }
        // check if expired
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token', 'status' => 'error'], 401);
        }
        // get tokens payload
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
        
        //check matching record in database
        if(!Investment::where('username', $uuid->username)->where('password',$uuid->password)->exists()){
            return response(['message' => 'unauthenticated', 'status'=>'error'], 401);
        }
        $investment = Investment::where('username', $uuid->username)->where('password',$uuid->password)->first();
        //check if user is an investor
        if($investment->role !== "investor"){
            return response(['message' => 'unauthorized access', 'status'=>'error'], 401);
        }
        
        return $next($request);
    }
    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }
}
