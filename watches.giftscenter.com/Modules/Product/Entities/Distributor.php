<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    protected $table = 'distributor_tbl';

    protected $primaryKey = 'dist_id';

    protected $fillable = [];

}
