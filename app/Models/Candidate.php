<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    const POSITION_PRESIDENTE = "presidente";
    const POSITION_GOVERNADOR = "governador";
    const POSITION_SENADOR = "senador";
    const POSITION_DEPUTADO_FEDERAL = "deputado-federal";
    const POSITION_DEPUTADO_ESTADUAL = "deputado-estadual";

    protected $fillable = [
        'name',
        'number',
        'position',
    ];
}
