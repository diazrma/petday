<?php

return [
    // Verificação por IA (gratuita, local) de que a foto contém um pet.
    'ai_check' => [
        'enabled' => env('PET_AI_CHECK', true),
        'threshold' => (float) env('PET_AI_THRESHOLD', 0.6),
        'fail_open' => env('PET_AI_FAIL_OPEN', true),
        'timeout' => (int) env('PET_AI_TIMEOUT', 90),
        'node' => env('PET_AI_NODE_BINARY', 'node'),
    ],
];
