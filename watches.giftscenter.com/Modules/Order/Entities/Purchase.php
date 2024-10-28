<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'gc_purchases';


    protected $fillable = [
        'unique_no',
        'customer_no',
        'invoice_no',
        'store_no',
        'rating',
        'review',
        'is_rated',
        'is_shared',
        'ordered_date',
        'updated_date',
    ];

}
