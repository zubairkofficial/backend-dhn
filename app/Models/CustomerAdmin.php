<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdmin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id',];

    /**
     * Get the user that owns the CustomerAdmin.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
