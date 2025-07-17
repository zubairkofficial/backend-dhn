<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scheren extends Model
{
    use HasFactory;
    protected $fillable = ['file_name', 'data', 'user_id'];

    public function Scheren()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
