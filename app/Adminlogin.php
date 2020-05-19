<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;

class Adminlogin extends Model
{
    protected $table = 'adminlogin';
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $fillable = [
        'auth_id', 'email', 'firstname', 'lastname', 'waveid', 'position', 'department', 'staff_id', 'v1_token', 'password', 'girotoken',
    ];
    protected $hidden = [
        'auth_id', 'email', 'waveid', 'position', 'department', 'staff_id', 'v1_token', 'password', 'girotoken',
    ]; 

    public function resourcerequest(){
        return $this->hasMany(Resourcerequest::class,'authid','authid');
    }

}
