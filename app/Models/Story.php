<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    protected $fillable = ['pet_id', 'user_id', 'image', 'caption', 'caption_x', 'caption_y', 'background', 'sticker', 'mood', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(StoryReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StoryComment::class);
    }

    /** Centro da legenda/sticker em %: o que o tutor escolheu, ou um padrão que não cobre o pet. */
    public function captionPosition(): array
    {
        return [
            'x' => $this->caption_x ?? 50,
            'y' => $this->caption_y ?? ($this->image ? 58 : 40),
        ];
    }

    public function moodInfo(): ?array
    {
        return $this->mood ? (\App\Support\Catalog::MOODS[$this->mood] ?? null) : null;
    }

    /** Fração de vida restante (1 = acabou de sair, 0 = vai sumir). */
    public function lifeLeft(): float
    {
        return max(0, min(1, now()->diffInSeconds($this->expires_at, false) / 86400));
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('expires_at', '>', now());
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
