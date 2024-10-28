<?php

namespace Modules\Cart\Entities;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'gc_wishlist';

    protected $primaryKey = 'wish_id';

    protected $fillable = [
        'customer_id',
        'product_id',
        'is_bundle',
        'added_date',
    ];


}
