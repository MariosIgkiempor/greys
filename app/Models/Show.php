<?php

namespace App\Models;

use Database\Factories\ShowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Show extends Model
{
    /** @use HasFactory<ShowFactory> */
    use HasFactory;

    protected $guarded = [];

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function className(): string
    {
        return match ($this->code) {
            'GA' => 'bg-green-500 text-black',
            'PP' => 'bg-slate-800',
            'S19' => 'bg-purple-300',
            'SG:OC', 'GA:BT', 'SG:MOH' => 'bg-gray-400 text-black',
            default => dd("Unknown show code: {$this->code}"),
        };
    }
}
