<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryInvoice extends Model
{
    protected $table = 'deliver_invoice_addr';


    protected $fillable = [
        'address',
        'street_number',
        'street',
        'city',
        'state',
        'zip_code',
        'country_name',
        'invoice_no',
        'phone',
        'customer_name',
        'delphone',
        'total_amount',
        'delsms',
        'confsms',
        'bill_address',
        'bill_street_number',
        'bill_street',
        'bill_city',
        'bill_state',
        'bill_zip_code',
        'bill_country',
        'delivery_date',
        'delivery_phone',
        'gift_wrap',
        'gift_box',
        'gift_message',
        'pickfrm_store',
        'sample',
        'referral_url',
        'delivery_status'
    ];



}
