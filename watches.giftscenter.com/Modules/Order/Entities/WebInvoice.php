<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class WebInvoice extends Model
{
    protected $table = 'web_invoice';


    protected $fillable = ['sl_no'];


}