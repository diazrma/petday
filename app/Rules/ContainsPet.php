<?php

namespace App\Rules;

use App\Services\PetImageDetector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * A foto precisa ter um pet (pessoas junto são permitidas).
 */
class ContainsPet implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.pet_ai.enabled') || ! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $result = app(PetImageDetector::class)->analyze($value->getRealPath());

        if ($result === null) {
            if (! config('services.pet_ai.fail_open')) {
                $fail('Não conseguimos verificar a foto agora. Tente novamente em instantes.');
            }

            return;
        }

        if (! $result['has_pet']) {
            $fail('Nossa IA não encontrou nenhum pet nesta foto 🐾 No PetDay toda foto precisa ter um bichinho (pode ter gente junto!).');
        }
    }
}
