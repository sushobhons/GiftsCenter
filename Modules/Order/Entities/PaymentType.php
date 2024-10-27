<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $table = 'payment_type_table';

    protected $primaryKey = 'pay_id';

    protected $fillable = [];


}
