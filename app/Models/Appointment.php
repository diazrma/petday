<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = ['pet_id', 'tutor_id', 'vet_id', 'scheduled_at', 'type', 'reason', 'status', 'vet_notes'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('scheduled_at', '>=', now())->whereIn('status', ['pending', 'confirmed']);
    }

    public function typeLabel(): string
    {
        return Catalog::APPOINTMENT_TYPES[$this->type] ?? $this->type;
    }

    public function statusInfo(): array
    {
        return Catalog::APPOINTMENT_STATUS[$this->status] ?? ['label' => $this->status, 'class' => 'bg-slate-100'];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}
