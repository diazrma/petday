<x-layouts.app title="Consultas">
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-4xl font-bold">🩺 Consultas</h1>
        @php $pets = auth()->user()->pets; @endphp
        @if ($pets->count() === 1)
            <a href="{{ route('appointments.create', $pets->first()) }}" class="btn-primary">＋ Agendar</a>
        @elseif ($pets->count() > 1)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="btn-primary">＋ Agendar para…</button>
                <div x-cloak x-show="open" @click.outside="open = false" class="card absolute right-0 z-10 mt-2 w-56 p-2">
                    @foreach ($pets as $p)<a href="{{ route('appointments.create', $p) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 font-bold hover:bg-stone-50"><x-pet-avatar :pet="$p" size="xs" /> {{ $p->name }}</a>@endforeach
                </div>
            </div>
        @endif
    </div>

    <div data-infinite-items class="space-y-3">
        @forelse ($appointments as $a)
            <div class="card flex flex-wrap items-center gap-4 p-4 {{ $a->isOpen() && $a->scheduled_at->isFuture() ? '' : 'opacity-70' }}">
                <div class="flex w-16 flex-col items-center rounded-2xl bg-sky-50 py-2 text-sky-700">
                    <span class="text-[10px] font-extrabold uppercase">{{ $a->scheduled_at->locale('pt_BR')->translatedFormat('M') }}</span>
                    <span class="font-display text-2xl font-bold leading-none">{{ $a->scheduled_at->format('d') }}</span>
                    <span class="text-xs font-bold">{{ $a->scheduled_at->format('H:i') }}</span>
                </div>
                <x-pet-avatar :pet="$a->pet" size="md" />
                <div class="min-w-[9rem] flex-1 text-sm">
                    <p class="font-extrabold">{{ $a->pet->name }} · {{ $a->typeLabel() }}</p>
                    <p class="text-stone-500">{{ $a->vet->clinic_name }} · Dr(a). {{ $a->vet->name }}</p>
                    @if ($a->vet_notes)<p class="mt-1 rounded-xl bg-emerald-50 p-2 text-emerald-800"><b>Parecer:</b> {{ $a->vet_notes }}</p>@endif
                </div>
                <span class="chip {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span>
                @if ($a->isOpen())
                    <form method="POST" action="{{ route('appointments.cancel', $a) }}" data-confirm-title="Cancelar consulta?" data-confirm="O veterinário será avisado do cancelamento." data-confirm-button="Cancelar consulta" data-confirm-icon="📅">@csrf @method('PATCH')<button class="btn-ghost !px-3 !py-1.5 text-xs text-rose-600">Cancelar</button></form>
                @endif
            </div>
        @empty
            <div class="card p-10 text-center text-stone-500"><div class="text-5xl">🩺</div><p class="mt-2">Nenhuma consulta ainda. Agende pelo perfil do seu pet.</p></div>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$appointments" />
</div>
</x-layouts.app>
