<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Investmentstart extends Model
{
    protected $fillable = ['amount','duration','referal_code','investment_start_date','savings_id','firstname','lastname','savings_account_no','stage','unique_number','next_interest','status'];
}
