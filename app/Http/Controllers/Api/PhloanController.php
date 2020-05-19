<?php

namespace App\Http\Controllers\Api;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
// use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PhloanController extends Controller
{
    public function store(Request $request){
        // $header = $request->header('Authorization');  
    }
    private function validateStore()
    {
        return request()->validate([
            'auth_id' => 'required|numeric',
            'title'  => 'required|email',
            'description'  => 'required|string',
            'type'  => 'required|string',
            'amount'   => 'required|numeric',
            'initial_approved_by'   => 'string',
            'final_approved_by'   => 'string',
            'initial_approved_date' => 'date|date_format:Y-m-d',
            'final_approved_date' => 'date|date_format:Y-m-d',
            'created_by'  => 'required|string',
            'status'  => 'numeric'
        ]);
    }
}
