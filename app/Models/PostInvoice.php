<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostInvoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'invoice_number',
        'category',
        'currency_code',
        'date',
        'price',
        'quantity',
        'due_date',
        'document_type',
        'description',
        'tax',
        'subtotal',
        'total',
        'current_datetime',
    ];
}
