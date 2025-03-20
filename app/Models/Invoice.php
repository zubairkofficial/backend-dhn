<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'invoice_number',
        'category',
        'currency_code',
        'date',
        'due_date',
        'document_type',
        'subtotal',
        'tax',
        'total',
        'description',
        'created_at',
        'latest_added_time',
        'quantity',
        'price',
    ];
}
