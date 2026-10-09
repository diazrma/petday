<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Verifica, com IA gratuita local (CLIP via Transformers.js), se a foto contém um pet.
 * Pessoas podem aparecer junto, mas o pet é obrigatório.
 */
class PetDetector
{
    /** @return array{ok: bool, score: float|null, label: string|null, skipped?: bool} */
    public function check(UploadedFile $file): array
    {
        if (! config('petday.ai_check.enabled')) {
            return ['ok' => true, 'score' => null, 'label' => null, 'skipped' => true];
        }

        $result = Process::timeout(config('petday.ai_check.timeout'))
            ->env(['PET_AI_THRESHOLD' => (string) config('petday.ai_check.threshold')])
            ->run([config('petday.ai_check.node'), base_path('scripts/detect-pet.mjs'), $file->getRealPath()]);

        $data = json_decode(trim($result->output()), true);

        if (! is_array($data) || isset($data['error'])) {
            Log::warning('PetDetector falhou', ['output' => $result->output(), 'error' => $result->errorOutput()]);

            // Se a IA estiver indisponível, decide por configuração se bloqueia ou libera.
            return ['ok' => (bool) config('petday.ai_check.fail_open'), 'score' => null, 'label' => null, 'skipped' => true];
        }

        return ['ok' => (bool) $data['has_pet'], 'score' => $data['score'], 'label' => $data['label']];
    }
}
