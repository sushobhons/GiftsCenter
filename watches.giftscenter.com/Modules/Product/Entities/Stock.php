<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    protected $table = 'stock_table';

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\StockFactory::new();
    }
}
