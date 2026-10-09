@php use App\Support\Catalog; @endphp
<x-layouts.app title="Minha clínica">
<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <p class="font-bold text-sky-600">Painel do veterinário</p>
        <h1 class="font-display text-4xl font-bold">🏥 {{ auth()->user()->clinic_name ?? 'Minha clínica' }}</h1>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([['Hoje', $stats['today'], 'bg-sky-50 text-sky-700'], ['Aguardando resposta', $stats['pending'], 'bg-amber-50 text-amber-700'], ['Atendimentos na semana', $stats['week'], 'bg-emerald-50 text-emerald-700'], ['Pacientes', $stats['patients'], 'bg-stone-100 text-stone-700']] as [$label, $value, $cls])
            <div class="rounded-3xl p-4 {{ $cls }}"><p class="text-xs font-extrabold uppercase opacity-70">{{ $label }}</p><p class="font-display text-4xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
        @foreach ($week as $d)
            <a href="{{ route('vet.index', ['dia' => $d['date']->toDateString()]) }}" class="flex w-20 shrink-0 flex-col items-center rounded-2xl p-3 {{ $day?->isSameDay($d['date']) ? 'bg-sky-500 text-white' : 'card' }}">
                <span class="text-xs font-extrabold uppercase opacity-70">{{ $d['date']->locale('pt_BR')->translatedFormat('D') }}</span>
                <span class="font-display text-2xl font-bold">{{ $d['date']->format('d') }}</span>
                <span class="text-xs font-bold">{{ $d['count'] }} {{ $d['count'] === 1 ? 'consulta' : 'consultas' }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('vet.index') }}" class="chip !px-3 !py-1.5 {{ ! $status && ! $day ? 'bg-night text-white' : 'bg-surface' }}">Próximas</a>
        @foreach (Catalog::APPOINTMENT_STATUS as $k => $s)
            <a href="{{ route('vet.index', ['status' => $k]) }}" class="chip !px-3 !py-1.5 {{ $status === $k ? 'bg-night text-white' : $s['class'] }}">{{ $s['label'] }}</a>
        @endforeach
    </div>

    <div data-infinite-items class="mt-4 space-y-3">
        @forelse ($appointments as $a)
            <div class="card p-4" x-data="{ open: false }">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-16 text-center"><p class="font-display text-2xl font-bold">{{ $a->scheduled_at->format('H:i') }}</p><p class="text-xs font-bold text-stone-400">{{ $a->scheduled_at->format('d/m') }}</p></div>
                    <a href="{{ route('pets.show', [$a->pet, 'aba' => 'saude']) }}"><x-pet-avatar :pet="$a->pet" size="md" /></a>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-extrabold">{{ $a->pet->name }} <span class="font-semibold text-stone-500">· {{ $a->pet->speciesInfo()['label'] }}{{ $a->pet->breed ? ', '.$a->pet->breed : '' }}{{ $a->pet->ageLabel() ? ', '.$a->pet->ageLabel() : '' }}</span></p>
                        <p class="text-stone-500">{{ $a->typeLabel() }} · Tutor: {{ $a->pet->owner->name }}</p>
                        @if ($a->reason)<p class="mt-1 text-stone-700">“{{ $a->reason }}”</p>@endif
                    </div>
                    <span class="chip {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span>
                    @if ($a->isOpen())<button @click="open = !open" class="btn-soft !py-1.5 text-xs">Atender</button>@endif
                </div>
                @if ($a->isOpen())
                    <form x-show="open" x-collapse method="POST" action="{{ route('vet.update', $a) }}" class="mt-4 space-y-3 border-t border-stone-100 pt-4">
                        @csrf @method('PATCH')
                        <textarea name="vet_notes" rows="2" class="input resize-none" placeholder="Parecer / orientações para o tutor (opcional)">{{ $a->vet_notes }}</textarea>
                        <div class="flex flex-wrap gap-2">
                            @if ($a->status === 'pending')<button name="status" value="confirmed" class="btn !bg-sky-500 text-white">✅ Confirmar</button>@endif
                            <button name="status" value="completed" class="btn !bg-emerald-500 text-white">🩺 Marcar como realizada</button>
                            <button name="status" value="declined" class="btn-danger">Recusar</button>
                        </div>
                    </form>
                @elseif ($a->vet_notes)
                    <p class="mt-3 rounded-xl bg-emerald-50 p-2 text-sm text-emerald-800"><b>Parecer:</b> {{ $a->vet_notes }}</p>
                @endif
            </div>
        @empty
            <div class="card p-10 text-center text-stone-500">Nenhuma consulta encontrada.</div>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$appointments" />
</div>
</x-layouts.app>
