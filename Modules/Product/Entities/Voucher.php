<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'voucher_table';
    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'status',
        'VALIDITY',
        'customer_id',
        'activate_date',
        'edetails',
    ];


}
