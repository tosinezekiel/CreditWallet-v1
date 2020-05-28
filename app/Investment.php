<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Investment extends Model
{

    protected $append = ['role'];

    protected $table = 'investment_login';

    protected $fillable = [
        'email', 'username','borrower_id', 'password', 'first_login' 
    ];

    public function getRoleAttribute(){
        return "investor";
    }
    // public function getJWTIdentifier()
    // {
    //     return $this->getKey();
    // }

    // public function getJWTCustomClaims()
    // {
    //     return [];
    // }
}
