<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;

class UserLoyaltyPoint extends Model
{
    protected $table = 'customer_points';


    protected $fillable = [
        'customer_id',
        'point_in',
        'point_out',
        'point_type',
        'pre_balance',
        'post_balance',
        'note',
        'note1',
        'location',
        'added_date',
        'valid_points',
        'valid_date',
        'valid_days',
    ];


}
