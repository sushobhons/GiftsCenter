<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'store_table';

    protected $primaryKey = 'store_id';

    protected $fillable = [];


}
