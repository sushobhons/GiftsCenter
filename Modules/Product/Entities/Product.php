<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    protected $table = 'product_table';
    protected $primaryKey = 'product_id';

    protected $fillable = [];

    public function mainCategory()
    {
        return $this->belongsTo('Modules\Product\Entities\MainCategory', 'main_cat_id');
    }

    public function category()
    {
        return $this->belongsTo('Modules\Product\Entities\Category', 'cat_id');
    }

    public function subCategory()
    {
        return $this->belongsTo('Modules\Product\Entities\SubCategory', 'sub_cat_id');
    }

    public function stock()
    {
        return $this->hasOne('Modules\Product\Entities\Stock', 'product_id');
    }

    public function brand()
    {
        return $this->belongsTo('Modules\Product\Entities\Brand', 'brand_id', 'id');
    }
}
