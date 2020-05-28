<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Investmentstart extends Model
{
    protected $fillable = ['amount','duration','referal_code','investment_start_date'];
}
