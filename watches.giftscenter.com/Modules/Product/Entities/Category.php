<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    protected $table = 'cat_table';
    protected $primaryKey = 'cat_id';

    protected $fillable = [];

    public function mainCategory()
    {
        return $this->belongsTo('Modules\Product\Entities\MainCategory', 'main_cat_id');
    }

    public function products()
    {
        return $this->hasMany('Modules\Product\Entities\Product', 'cat_id');
    }

    public function subCategories()
    {
        return $this->hasMany('Modules\Product\Entities\SubCategory', 'cat_id');
    }

}
