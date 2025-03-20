<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeDataProcess extends Model
{
    use HasFactory;

    protected $fillable = ['file_name', 'data', 'user_id'];
    public function FreeDataProcess(){
        return $this->belongsTo(User::class , 'id');
    }
}
