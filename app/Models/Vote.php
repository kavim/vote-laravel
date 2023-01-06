<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'elector_id',
        'election_id',
        'presidente_number',
        'governador_number',
        'senador_number',
        'deputado_federal_number',
        'deputado_estadual_number',
    ];

    public function elector(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Elector::class);
    }

    public function election(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Election::class);
    }
}
