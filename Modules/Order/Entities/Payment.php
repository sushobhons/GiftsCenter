<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payment_table';

    protected $primaryKey = 'paymnt_id';

    protected $fillable = ['paymnt_invoice', 'paymnt_type', 'paymnt_amount', 'refund_amnt', 'npaymnt_type', 'npaymnt_amount', 'paymnt_card', 'paymnt_name', 'paymnt_date', 'company_id', 'store_id', 'paymnt_date_char', 'new_status', 'all_update', 'posted_date', 'customer', 'old_invoice', 'acc_no', 'status', 'store_no'];


}
