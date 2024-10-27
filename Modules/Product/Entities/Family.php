<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $table = 'family_tbl';

    protected $fillable = [];

    public function products()
    {
        return $this->hasMany('Modules\Product\Entities\Product', 'linepr', 'family_id');
    }
}
