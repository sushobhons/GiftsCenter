<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $table = 'gc_survey';


    protected $fillable = ['customer_id', 'source', 'added_date'];


}