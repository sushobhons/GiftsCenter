<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCategory extends Model
{
    protected $table = 'sub_cat_table';
    protected $primaryKey = 'sub_cat_id';

    protected $fillable = [];

    public function category()
    {
        return $this->belongsTo('Modules\Product\Entities\Category', 'cat_id');
    }

    public function products()
    {
        return $this->hasMany('Modules\Product\Entities\Product', 'sub_cat_id');
    }

}
