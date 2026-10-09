<?php

namespace App\Support;

/**
 * Catálogos fixos usados em formulários, badges e no calendário.
 */
class Catalog
{
    public const SPECIES = [
        'dog' => ['label' => 'Cachorro', 'emoji' => '🐶'],
        'cat' => ['label' => 'Gato', 'emoji' => '🐱'],
        'bird' => ['label' => 'Ave', 'emoji' => '🦜'],
        'rodent' => ['label' => 'Roedor', 'emoji' => '🐹'],
        'rabbit' => ['label' => 'Coelho', 'emoji' => '🐰'],
        'reptile' => ['label' => 'Réptil', 'emoji' => '🦎'],
        'fish' => ['label' => 'Peixe', 'emoji' => '🐠'],
        'other' => ['label' => 'Outro', 'emoji' => '🐾'],
    ];

    public const MOODS = [
        'happy' => ['label' => 'Feliz', 'emoji' => '😄', 'color' => '#facc15'],
        'playful' => ['label' => 'Brincalhão', 'emoji' => '🎾', 'color' => '#22c55e'],
        'sleepy' => ['label' => 'Sonolento', 'emoji' => '😴', 'color' => '#818cf8'],
        'hungry' => ['label' => 'Com fome', 'emoji' => '🍖', 'color' => '#f97316'],
        'loved' => ['label' => 'Amado', 'emoji' => '🥰', 'color' => '#ec4899'],
        'grumpy' => ['label' => 'Rabugento', 'emoji' => '😾', 'color' => '#64748b'],
        'sick' => ['label' => 'Dodói', 'emoji' => '🤒', 'color' => '#ef4444'],
    ];

    public const POST_TYPES = [
        'moment' => ['label' => 'Momento', 'emoji' => '📸'],
        'milestone' => ['label' => 'Marco', 'emoji' => '🏆'],
        'walk' => ['label' => 'Passeio', 'emoji' => '🦮'],
        'meal' => ['label' => 'Refeição', 'emoji' => '🥣'],
        'play' => ['label' => 'Brincadeira', 'emoji' => '🧸'],
        'nap' => ['label' => 'Soneca', 'emoji' => '💤'],
        'health' => ['label' => 'Saúde', 'emoji' => '🩺'],
    ];

    public const APPOINTMENT_TYPES = [
        'checkup' => 'Check-up',
        'vaccine' => 'Vacinação',
        'exam' => 'Exames',
        'emergency' => 'Emergência',
        'surgery' => 'Cirurgia',
        'grooming' => 'Banho & Tosa',
        'return' => 'Retorno',
    ];

    public const APPOINTMENT_STATUS = [
        'pending' => ['label' => 'Aguardando', 'class' => 'bg-amber-100 text-amber-800'],
        'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-sky-100 text-sky-800'],
        'completed' => ['label' => 'Realizada', 'class' => 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-slate-200 text-slate-700'],
        'declined' => ['label' => 'Recusada', 'class' => 'bg-rose-100 text-rose-800'],
    ];

    public const HEALTH_KINDS = [
        'vaccine' => ['label' => 'Vacina', 'emoji' => '💉'],
        'deworming' => ['label' => 'Vermífugo', 'emoji' => '💊'],
        'medication' => ['label' => 'Medicação', 'emoji' => '🧪'],
        'exam' => ['label' => 'Exame', 'emoji' => '🔬'],
        'weight' => ['label' => 'Pesagem', 'emoji' => '⚖️'],
    ];

    public const PERSONALITY = [
        'Brincalhão', 'Dorminhoco', 'Guloso', 'Carente', 'Aventureiro', 'Tímido',
        'Bagunceiro', 'Protetor', 'Sociável', 'Preguiçoso', 'Curioso', 'Dramático',
    ];

    public const STORY_BACKGROUNDS = [
        'from-orange-400 to-pink-500',
        'from-violet-500 to-fuchsia-500',
        'from-sky-400 to-emerald-400',
        'from-amber-300 to-orange-500',
        'from-rose-400 to-red-500',
        'from-slate-700 to-slate-900',
    ];

    /** Reações dos rastros: coisas que pet entende, não "curtir". */
    public const STORY_REACTIONS = [
        'treat' => ['emoji' => '🦴', 'label' => 'Petisco', 'verb' => 'deu um petisco'],
        'hug' => ['emoji' => '🤗', 'label' => 'Carinho', 'verb' => 'fez carinho'],
        'lol' => ['emoji' => '😂', 'label' => 'Rolei', 'verb' => 'rolou de rir'],
        'cute' => ['emoji' => '🥺', 'label' => 'Que fofo', 'verb' => 'derreteu'],
    ];

    public const PET_COLORS = ['#f97316', '#ec4899', '#8b5cf6', '#0ea5e9', '#10b981', '#eab308', '#ef4444', '#64748b'];

    public static function species(?string $key): array
    {
        return self::SPECIES[$key] ?? self::SPECIES['other'];
    }

    public static function mood(?string $key): ?array
    {
        return $key ? (self::MOODS[$key] ?? null) : null;
    }

    public static function postType(?string $key): array
    {
        return self::POST_TYPES[$key] ?? self::POST_TYPES['moment'];
    }
}
