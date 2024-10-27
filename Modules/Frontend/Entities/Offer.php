<?php

namespace Modules\Frontend\Entities;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'offer_tbl';
    protected $primaryKey = 'offer_id';

    protected $fillable = [];
}

