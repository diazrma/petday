<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'avatar', 'bio', 'city',
        'clinic_name', 'crmv', 'specialty', 'clinic_address', 'active_pet_id',
        'banned_at', 'last_seen_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function activePet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'active_pet_id');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'follows')->withTimestamps();
    }

    public function paws(): HasMany
    {
        return $this->hasMany(Paw::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function tutorAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'tutor_id');
    }

    public function vetAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'vet_id');
    }

    public function scopeVets(Builder $q): Builder
    {
        return $q->where('role', 'vet')->whereNull('banned_at');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVet(): bool
    {
        return $this->role === 'vet';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /** Pet usado para postar/comentar; cai para o primeiro pet se o ativo sumiu. */
    public function currentPet(): ?Pet
    {
        if ($this->activePet && $this->activePet->user_id === $this->id) {
            return $this->activePet;
        }

        return $this->pets()->oldest()->first();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function initials(): string
    {
        return mb_strtoupper(collect(explode(' ', $this->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join(''));
    }

    public function roleLabel(): string
    {
        return ['admin' => 'Administrador', 'vet' => 'Veterinário(a)', 'tutor' => 'Tutor(a)'][$this->role] ?? $this->role;
    }
}
