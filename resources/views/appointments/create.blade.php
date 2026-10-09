@php use App\Support\Catalog; @endphp
<x-layouts.app title="Agendar consulta">
<div class="mx-auto max-w-2xl" x-data="{
        vet: '{{ old('vet_id', $vets->first()?->id) }}',
        date: '{{ old('date', now()->addDay()->toDateString()) }}',
        time: '{{ old('time') }}',
        busy: {{ json_encode($busy) }},
        slots: ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30'],
        taken(s) { return (this.busy[this.vet] || []).includes(this.date + ' ' + s) },
        past(s) { return new Date(this.date + 'T' + s) < new Date() },
     }">
    <div class="mb-6 flex items-center gap-3">
        <x-pet-avatar :pet="$pet" size="lg" />
        <div><h1 class="font-display text-3xl font-bold">Agendar consulta</h1><p class="text-stone-500">para {{ $pet->name }} {{ $pet->emoji() }}</p></div>
    </div>

    @if ($vets->isEmpty())
        <div class="card p-8 text-center text-stone-500">Ainda não há clínicas cadastradas no PetDay.</div>
    @else
    <form method="POST" action="{{ route('appointments.store', $pet) }}" class="space-y-5">
        @csrf
        @if ($errors->any())<ul class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>@endif

        <section class="card p-5">
            <h2 class="label">1. Escolha a clínica</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($vets as $v)
                    <label class="cursor-pointer"><input type="radio" name="vet_id" value="{{ $v->id }}" x-model="vet" class="peer sr-only">
                        <div class="flex h-full gap-3 rounded-2xl bg-stone-50 p-4 ring-2 ring-transparent peer-checked:bg-sky-50 peer-checked:ring-sky-400">
                            <x-user-avatar :user="$v" size="size-12 text-sm" />
                            <div class="min-w-0 text-sm">
                                <p class="font-extrabold">{{ $v->clinic_name }}</p>
                                <p class="text-stone-500">Dr(a). {{ $v->name }}{{ $v->specialty ? ' · '.$v->specialty : '' }}</p>
                                <p class="text-xs text-stone-400">{{ $v->crmv ? 'CRMV '.$v->crmv : '' }}{{ $v->city ? ' · '.$v->city : '' }}</p>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="card p-5">
            <h2 class="label">2. Tipo de atendimento</h2>
            <div class="flex flex-wrap gap-2">
                @foreach (Catalog::APPOINTMENT_TYPES as $k => $label)
                    <label class="cursor-pointer"><input type="radio" name="type" value="{{ $k }}" @checked(old('type', 'checkup') === $k) class="peer sr-only"><span class="chip bg-stone-100 !px-4 !py-2 text-sm text-stone-600 peer-checked:bg-sky-500 peer-checked:text-white">{{ $label }}</span></label>
                @endforeach
            </div>
        </section>

        <section class="card p-5">
            <h2 class="label">3. Data e horário</h2>
            <input type="date" name="date" x-model="date" min="{{ now()->toDateString() }}" max="{{ now()->addDays(90)->toDateString() }}" class="input mb-4 sm:w-60">
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                <template x-for="s in slots" :key="s">
                    <label>
                        <input type="radio" name="time" :value="s" x-model="time" class="peer sr-only" :disabled="taken(s) || past(s)">
                        <span class="block cursor-pointer rounded-xl bg-stone-50 py-2 text-center text-sm font-extrabold peer-checked:bg-sky-500 peer-checked:text-white peer-disabled:cursor-not-allowed peer-disabled:bg-stone-100 peer-disabled:text-stone-300 peer-disabled:line-through" x-text="s"></span>
                    </label>
                </template>
            </div>
        </section>

        <section class="card p-5">
            <label class="label" for="reason">4. Motivo / sintomas (opcional)</label>
            <textarea id="reason" name="reason" rows="3" maxlength="500" class="input resize-none" placeholder="Ex.: está coçando muito a orelha há 3 dias">{{ old('reason') }}</textarea>
        </section>

        <button class="btn-primary w-full !bg-sky-500 !py-4 text-base !shadow-sky-500/30 hover:!bg-sky-600" :disabled="!time">Solicitar consulta 🩺</button>
    </form>
    @endif
</div>
</x-layouts.app>
