<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Werthenbach extends Model
{
    protected $fillable = ['file_name', 'data', 'user_id'];

    public function werthenbach()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
