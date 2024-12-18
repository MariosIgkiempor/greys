<?php

namespace App\Models;

use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    protected $casts = [
        'air_date' => 'date',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function watchEpisode()
    {
        if ($this->views()->where('user_id', Auth::id())->doesntExist()) {
            $this->views()->create([
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function unwatchEpisode()
    {
        $this->views()->where('user_id', Auth::id())->delete();
    }
}
