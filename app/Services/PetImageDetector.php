<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Verifica se uma imagem contém um animal usando o CLIP local (scripts/pet-detector.mjs).
 * Gratuito e sem API externa — o modelo roda no próprio servidor via Node.
 */
class PetImageDetector
{
    /** @return array{has_pet: bool, score: float, label: ?string}|null null quando a IA não pôde rodar */
    public function analyze(string $path): ?array
    {
        $result = Process::timeout((int) config('services.pet_ai.timeout'))
            ->path(base_path())
            ->run([config('services.pet_ai.node'), 'scripts/pet-detector.mjs', $path]);

        if ($result->failed()) {
            Log::warning('Detector de pets falhou', ['error' => $result->errorOutput()]);

            return null;
        }

        $data = json_decode($result->output(), true);

        return is_array($data) && isset($data['has_pet']) ? $data : null;
    }
}
