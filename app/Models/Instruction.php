<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    use HasFactory;


    protected $fillable = ['title'];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_instruction');
    }
}
