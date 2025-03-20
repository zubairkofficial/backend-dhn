<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'Datum',
        'Thema',
        'Teilnehmer',
        'Niederlassungsleiter',
        'auther',
        'BM',
        'json_data',
    ];
}
