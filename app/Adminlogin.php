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
        'authid', 'email', 'firstname', 'lastname', 'wavied', 'position', 'department', 'staff_id', 'v1_token', 'password', 'girotoken',
    ];
    protected $hidden = [
        'authid', 'email', 'wavied', 'position', 'department', 'staff_id', 'v1_token', 'password', 'girotoken',
    ]; 

    public function resourcerequest(){
        return $this->hasMany(Resourcerequest::class,'authid','authid');
    }

}
