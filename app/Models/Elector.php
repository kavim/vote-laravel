<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elector extends Model
{
    use HasFactory;

    public function hasVoted(): bool
    {
        return $this->hasOne(Vote::class)->exists();
    }
}
