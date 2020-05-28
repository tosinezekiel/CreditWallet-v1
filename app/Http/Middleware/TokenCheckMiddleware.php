<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
class TokenCheckMiddleware
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
        // if(! $token = JWTAuth::getToken()){
        //     return response(['message' => 'unauthorized access', 'status'=>'error'], 401);
        // }
        // // check if expired
        // if(empty(JWTAuth::parseToken()->check())){
        //     return response(['message' => 'expired token', 'status' => 'error'], 401);
        // }
        // // get tokens payload
        // $apy = $this->getTokensPayload();
        // $uuid = $apy['uuid'];
        
        return $next($request);
    }
}
