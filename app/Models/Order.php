<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['customer', 'payed'];

    public function customerDetail(){
        return $this->hasOne('App\Models\Customer','id','customer');
    }

    public function orderProductMapping(){
        return $this->hasMany('App\Models\OrderProduct', 'order_id', 'id');
    }
}
