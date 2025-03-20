<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    public function instructions()
    {
        return $this->belongsToMany(Instruction::class, 'organization_instruction');
    }
}
