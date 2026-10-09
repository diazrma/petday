<?php

namespace App\Services;

use App\Models\Pet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta o "Diário" mensal de um pet: posts, consultas, lembretes de saúde e aniversário por dia.
 */
class PetCalendar
{
    public function __construct(public Pet $pet, public Carbon $month) {}

    public static function for(Pet $pet, ?string $month): self
    {
        try {
            $date = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $date = now()->startOfMonth();
        }

        return new self($pet, $date);
    }

    /** @return array<int, array<int, array>> semanas -> dias */
    public function weeks(): array
    {
        $start = $this->month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $this->month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $posts = $this->pet->posts()->visible()
            ->whereBetween('diary_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('created_at')->get()
            ->groupBy(fn ($p) => $p->diary_date->toDateString());

        $appointments = $this->pet->appointments()->with('vet')
            ->whereBetween('scheduled_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'declined'])
            ->get()->groupBy(fn ($a) => $a->scheduled_at->toDateString());

        $health = $this->pet->healthRecords()
            ->whereBetween('next_due_on', [$start->toDateString(), $end->toDateString()])
            ->get()->groupBy(fn ($h) => $h->next_due_on->toDateString());

        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                /** @var Collection $dayPosts */
                $dayPosts = $posts->get($key, collect());
                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $this->month->month,
                    'isToday' => $cursor->isToday(),
                    'isFuture' => $cursor->isFuture() && ! $cursor->isToday(),
                    'posts' => $dayPosts,
                    'cover' => $dayPosts->firstWhere('image', '!=', null),
                    'mood' => $dayPosts->whereNotNull('mood')->last()?->moodInfo(),
                    'paws' => $dayPosts->sum('paws_count'),
                    'appointments' => $appointments->get($key, collect()),
                    'health' => $health->get($key, collect()),
                    'birthday' => $this->pet->birthdate && $this->pet->birthdate->format('m-d') === $cursor->format('m-d'),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /** Resumo do mês: dias registrados, humor predominante, patinhas recebidas. */
    public function summary(): array
    {
        $posts = $this->pet->posts()->visible()
            ->whereBetween('diary_date', [$this->month->copy()->startOfMonth()->toDateString(), $this->month->copy()->endOfMonth()->toDateString()])
            ->get();

        $topMood = $posts->whereNotNull('mood')->countBy('mood')->sortDesc()->keys()->first();

        return [
            'days' => $posts->pluck('diary_date')->map->toDateString()->unique()->count(),
            'posts' => $posts->count(),
            'paws' => $posts->sum('paws_count'),
            'topMood' => $topMood ? \App\Support\Catalog::mood($topMood) : null,
        ];
    }

    public function prev(): string
    {
        return $this->month->copy()->subMonth()->format('Y-m');
    }

    public function next(): string
    {
        return $this->month->copy()->addMonth()->format('Y-m');
    }

    public function label(): string
    {
        return ucfirst($this->month->locale('pt_BR')->translatedFormat('F \d\e Y'));
    }
}
