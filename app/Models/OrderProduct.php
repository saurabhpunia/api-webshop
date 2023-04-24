<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    protected $table = 'order_product';

    public function orderDetail(){
        return $this->hasOne('App\Models\Order','id','order_id');
    }

    public function productDetail(){
        return $this->hasOne('App\Models\Product','id','product_id');
    }
}
