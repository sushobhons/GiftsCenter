<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brand_table';

    protected $fillable = [];

    public function products()
    {
        return $this->hasMany('Modules\Product\Entities\Product', 'brand_id', 'id');
    }
}
