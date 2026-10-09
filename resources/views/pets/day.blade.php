<x-layouts.app :title="$pet->name.' · '.$day->format('d/m')">
<div class="mx-auto max-w-xl space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('pets.show', [$pet, 'mes' => $day->format('Y-m')]) }}" class="btn-ghost !p-2.5">←</a>
        <x-pet-avatar :pet="$pet" size="md" />
        <div>
            <p class="text-sm font-bold text-stone-500">Diário de {{ $pet->name }}</p>
            <h1 class="font-display text-2xl font-bold">{{ ucfirst($day->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y')) }}</h1>
        </div>
        <div class="ml-auto flex gap-1">
            <a href="{{ route('pets.day', [$pet, $day->copy()->subDay()->toDateString()]) }}" class="btn-ghost !p-2" aria-label="Dia anterior">‹</a>
            @if ($day->lt(today()))<a href="{{ route('pets.day', [$pet, $day->copy()->addDay()->toDateString()]) }}" class="btn-ghost !p-2" aria-label="Próximo dia">›</a>@endif
        </div>
    </div>

    @if ($pet->birthdate && $pet->birthdate->format('m-d') === $day->format('m-d'))
        <div class="card bg-gradient-to-r from-pink-500 to-orange-400 p-4 text-center font-display text-xl font-semibold text-white">🎂 Aniversário de {{ $pet->name }}{{ $day->year > $pet->birthdate->year ? ' — '.($day->year - $pet->birthdate->year).' '.(($day->year - $pet->birthdate->year) === 1 ? 'ano' : 'anos') : '' }}!</div>
    @endif

    @foreach ($appointments as $a)
        <div class="card flex items-center gap-3 border-l-4 border-sky-400 p-4">
            <span class="text-3xl">🩺</span>
            <div class="flex-1 text-sm"><p class="font-extrabold">{{ $a->typeLabel() }} às {{ $a->scheduled_at->format('H:i') }}</p><p class="text-stone-500">{{ $a->vet->clinic_name }} · Dr(a). {{ $a->vet->name }}</p></div>
            <span class="chip {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span>
        </div>
    @endforeach
    @foreach ($health as $h)
        <div class="card flex items-center gap-3 border-l-4 border-amber-400 p-4 text-sm">
            <span class="text-3xl">{{ $h->kindInfo()['emoji'] }}</span>
            <p class="flex-1"><b>{{ $h->title }}</b><br><span class="text-stone-500">{{ $h->next_due_on?->isSameDay($day) ? 'Dose/retorno previsto para hoje' : 'Aplicado neste dia' }}</span></p>
        </div>
    @endforeach

    @forelse ($posts as $post)
        <x-post-card :post="$post" />
    @empty
        @if ($appointments->isEmpty() && $health->isEmpty())
            <div class="card p-10 text-center text-stone-500"><div class="text-5xl">📭</div><p class="mt-2">Nada registrado neste dia.</p></div>
        @endif
    @endforelse
</div>
</x-layouts.app>
