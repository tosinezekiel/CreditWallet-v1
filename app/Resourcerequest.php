<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resourcerequest extends Model
{
    protected $fillable = [
        'category', 'authid','amount', 'type','created_by', 'initial_approved_by', 'final_approved_by', 'initial_approved_date', 'final_approved_date', 'final_approved_date', 'status'
    ];

    public function creator(){
        return $this->belongsTo(Adminlogin::class,'authid','authid');
    }
}
