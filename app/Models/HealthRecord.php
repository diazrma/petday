<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    protected $fillable = ['pet_id', 'kind', 'title', 'applied_on', 'next_due_on', 'notes'];

    protected function casts(): array
    {
        return ['applied_on' => 'date', 'next_due_on' => 'date'];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function kindInfo(): array
    {
        return Catalog::HEALTH_KINDS[$this->kind] ?? ['label' => $this->kind, 'emoji' => '🩺'];
    }
}
