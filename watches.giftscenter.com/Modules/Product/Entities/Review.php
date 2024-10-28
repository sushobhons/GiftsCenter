<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'gc_reviews';


    protected $fillable = [
        'title',
        'description',
        'rating',
        'product_id',
        'is_bundle',
        'customer_id',
        'added_date',
    ];


}