<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainCategory extends Model
{
    protected $table = 'main_cat_table';
    protected $primaryKey = 'main_cat_id';

    protected $fillable = [];

    public function categories()
    {
        return $this->hasMany('Modules\Product\Entities\Category', 'main_cat_id');
    }

    public function products()
    {
        return $this->hasMany('Modules\Product\Entities\Product', 'main_cat_id');
    }
    

}
