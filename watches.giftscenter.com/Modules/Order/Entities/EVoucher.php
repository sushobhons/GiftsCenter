<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class EVoucher extends Model
{
    protected $table = 'gc_voucher';

    protected $fillable = [
        'voucher',
        'send_details',
        'send_date',
        'is_sent',
    ];


}
