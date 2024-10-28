<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class MontypayPayment extends Model
{


    protected $fillable = ['payment_id', 'order_number', 'data', 'status', 'transaction_type', 'transaction_status'];


}