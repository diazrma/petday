<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id', 'user_id', 'type', 'mood', 'body', 'image', 'location',
        'diary_date', 'paws_count', 'comments_count', 'hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'diary_date' => 'date',
            'hidden_at' => 'datetime',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paws(): HasMany
    {
        return $this->hasMany(Paw::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopeVisible(Builder $q): Builder
    {
        return $q->whereNull('hidden_at');
    }

    /** Adiciona a flag `pawed` indicando se o usuário já deu patinha. */
    public function scopeWithPawedBy(Builder $q, ?User $user): Builder
    {
        return $q->withExists(['paws as pawed' => fn ($p) => $p->where('user_id', $user?->id ?? 0)]);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function typeInfo(): array
    {
        return Catalog::postType($this->type);
    }

    public function moodInfo(): ?array
    {
        return Catalog::mood($this->mood);
    }
}
