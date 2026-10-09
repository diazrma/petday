<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryComment extends Model
{
    protected $fillable = ['story_id', 'user_id', 'pet_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    /** Quem aparece no recado: o pet ativo de quem escreveu, ou a própria pessoa. */
    public function authorName(): string
    {
        return $this->pet?->name ?? $this->user->name;
    }
}
