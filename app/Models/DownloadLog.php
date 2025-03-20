<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_name', 'downloaded_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
