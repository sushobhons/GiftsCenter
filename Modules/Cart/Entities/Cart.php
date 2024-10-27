<?php

namespace Modules\Cart\Entities;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'gc_cart';

    protected $fillable = [
        'cart_key',
        'customer_id',
        'product_id',
        'product_qty',
        'product_offer',
        'is_gift',
        'gift_for',
        'is_bundle',
        'is_voucher',
        'is_guest',
        'init_ccpay',
        'amnt_ccpay',
        'added_date',
        'evoucher_det',
    ];


}
