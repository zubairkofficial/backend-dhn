<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogoSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'logo',
    ];

    /**
     * Get the user that owns the logo setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
