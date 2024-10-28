<?php



namespace Modules\Frontend\Entities;



use Illuminate\Database\Eloquent\Model;



class GcNewsletter extends Model

{
    public $timestamps = false; // Disable timestamps

    protected $table = 'gc_newsletter';
    protected $primaryKey = 'newsletter_id';
    protected $fillable = [];
}

