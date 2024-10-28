<?php

namespace Modules\Frontend\Entities;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'gc_article';
    protected $primaryKey = 'article_id';
    protected $fillable = [];

}
