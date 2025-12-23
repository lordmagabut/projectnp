<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id', 'description', 'line_type', 'amount'
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
