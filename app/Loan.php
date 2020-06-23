<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = "loanapplications";
    protected $fillable = ['title', 'gender', 'telephone', 'firstname', 'lastname', 'email', 'house_address', 
    'city', 'state', 'place_of_work', 'loan_amount', 'tenor', 'salary_bank_name', 'salary_bank_account', 
    'ippisnumber', 'created_at', 'updated_at','monthly_repayment','dob','contactphone','giroreference','marketer','status', 'reason'];
    
}
