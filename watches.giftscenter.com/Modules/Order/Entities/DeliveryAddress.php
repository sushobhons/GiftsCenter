<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryAddress extends Model
{
    protected $table = 'gc_delivery_addresses';


    protected $fillable = ['customer_id', 'customer_name', 'customer_phone', 'customer_email', 'address', 'street_number', 'street', 'city', 'state', 'zip_code', 'country', 'is_default', 'is_selected'];



}
