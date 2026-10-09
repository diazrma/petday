<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'slug', 'species', 'breed', 'gender', 'birthdate',
        'weight', 'avatar', 'cover', 'bio', 'personality', 'color',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'personality' => 'array',
            'weight' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Pet $pet) {
            if (! $pet->slug) {
                $base = Str::slug($pet->name) ?: 'pet';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $pet->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows')->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function speciesInfo(): array
    {
        return Catalog::species($this->species);
    }

    public function emoji(): string
    {
        return $this->speciesInfo()['emoji'];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function ageLabel(): ?string
    {
        if (! $this->birthdate) {
            return null;
        }
        $diff = $this->birthdate->diff(now());
        if ($diff->y > 0) {
            return $diff->y.' '.($diff->y === 1 ? 'ano' : 'anos').($diff->m ? ' e '.$diff->m.' '.($diff->m === 1 ? 'mês' : 'meses') : '');
        }

        return max($diff->m, 0).' '.($diff->m === 1 ? 'mês' : 'meses');
    }

    public function isBirthdayToday(): bool
    {
        return $this->birthdate && $this->birthdate->format('m-d') === now()->format('m-d');
    }

    /** Dias consecutivos com pelo menos um registro no diário, terminando hoje ou ontem. */
    public function streak(): int
    {
        $dates = $this->posts()->whereNull('hidden_at')
            ->where('diary_date', '>=', now()->subDays(365)->toDateString())
            ->distinct()->orderByDesc('diary_date')->pluck('diary_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->values();

        $cursor = now()->startOfDay();
        if (! $dates->contains($cursor->toDateString())) {
            $cursor->subDay();
        }
        $streak = 0;
        while ($dates->contains($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    public function isFollowedBy(?User $user): bool
    {
        return $user && $this->followers()->whereKey($user->id)->exists();
    }
}
