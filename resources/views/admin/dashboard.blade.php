@php use App\Support\Catalog; $maxAct = max(1, $activity->max('posts'), $activity->max('users')); $maxSpecies = max(1, $species->max() ?? 1); @endphp
<x-layouts.admin title="Visão geral">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Usuários', $stats['users'], '+'.$stats['usersNew'].' nesta semana', '👥'],
            ['Online agora', $stats['online'], 'últimos 15 min', '🟢'],
            ['Pets', $stats['pets'], $stats['vets'].' veterinários', '🐶'],
            ['Posts', $stats['posts'], $stats['paws'].' patinhas', '🖼️'],
            ['Stories ativos', $stats['stories'], 'nas últimas 24h', '⏳'],
            ['Consultas futuras', $stats['appointments'], 'pendentes/confirmadas', '🩺'],
            ['Denúncias abertas', $stats['reports'], 'aguardando análise', '🚩'],
            ['Suspensos', $stats['banned'], 'contas bloqueadas', '⛔'],
        ] as [$label, $value, $hint, $icon])
            <div class="card p-5">
                <div class="flex items-start justify-between"><p class="text-xs font-extrabold uppercase text-stone-400">{{ $label }}</p><span class="text-xl">{{ $icon }}</span></div>
                <p class="mt-1 font-display text-4xl font-bold tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
                <p class="text-xs font-semibold text-stone-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Atividade — últimos 14 dias</h2>
                <div class="flex gap-3 text-xs font-bold text-stone-500"><span class="flex items-center gap-1"><span class="size-2.5 rounded-sm bg-brand-500"></span>Posts</span><span class="flex items-center gap-1"><span class="size-2.5 rounded-sm bg-sky-500"></span>Cadastros</span></div>
            </div>
            <div class="flex h-56 items-end gap-1.5">
                @foreach ($activity as $d)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        <div class="pointer-events-none absolute -top-2 z-10 hidden -translate-y-full whitespace-nowrap rounded-lg bg-night px-2 py-1 text-xs font-bold text-white group-hover:block">{{ $d['label'] }}: {{ $d['posts'] }} posts · {{ $d['users'] }} cadastros</div>
                        <div class="flex w-full flex-1 items-end justify-center gap-0.5">
                            <div class="w-1/2 rounded-t bg-brand-500 transition group-hover:bg-brand-600" style="height: {{ $d['posts'] / $maxAct * 100 }}%; min-height: {{ $d['posts'] ? 3 : 0 }}px"></div>
                            <div class="w-1/2 rounded-t bg-sky-500 transition group-hover:bg-sky-600" style="height: {{ $d['users'] / $maxAct * 100 }}%; min-height: {{ $d['users'] ? 3 : 0 }}px"></div>
                        </div>
                        <span class="mt-1 text-[10px] font-bold text-stone-400">{{ substr($d['label'], 0, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card p-5">
            <h2 class="mb-4 font-display text-xl font-semibold">Pets por espécie</h2>
            <div class="space-y-3">
                @forelse ($species as $key => $total)
                    <div>
                        <div class="mb-1 flex justify-between text-sm font-bold"><span>{{ Catalog::species($key)['emoji'] }} {{ Catalog::species($key)['label'] }}</span><span class="tabular-nums">{{ $total }}</span></div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $total / $maxSpecies * 100 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Sem pets ainda.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-5">
            <h2 class="mb-3 font-display text-xl font-semibold">🏆 Pets com mais patinhas</h2>
            @foreach ($topPets as $i => $pet)
                <a href="{{ route('pets.show', $pet) }}" class="flex items-center gap-3 py-2">
                    <span class="w-5 text-center font-display font-bold text-stone-400">{{ $i + 1 }}</span>
                    <x-pet-avatar :pet="$pet" size="sm" />
                    <span class="flex-1 font-bold">{{ $pet->name }}</span>
                    <span class="font-extrabold text-brand-600 tabular-nums">🐾 {{ (int) $pet->posts_sum_paws_count }}</span>
                </a>
            @endforeach
        </section>
        <section class="card p-5">
            <h2 class="mb-3 flex justify-between font-display text-xl font-semibold">Novos usuários <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-brand-600">ver todos</a></h2>
            @foreach ($latestUsers as $u)
                <a href="{{ route('admin.users.edit', $u) }}" class="flex items-center gap-3 py-2">
                    <x-user-avatar :user="$u" />
                    <span class="min-w-0 flex-1 text-sm"><b class="block truncate">{{ $u->name }}</b><span class="text-stone-500">{{ $u->roleLabel() }}</span></span>
                    <span class="text-xs text-stone-400">{{ $u->created_at->diffForHumans(null, true) }}</span>
                </a>
            @endforeach
        </section>
        <section class="card p-5">
            <h2 class="mb-3 flex justify-between font-display text-xl font-semibold">🚩 Denúncias <a href="{{ route('admin.reports.index') }}" class="text-xs font-bold text-brand-600">ver todas</a></h2>
            @forelse ($openReports as $r)
                <div class="border-t border-stone-100 py-2 text-sm first:border-0">
                    <p><b>{{ $r->user->name }}</b>: {{ $r->reason }}</p>
                    @if ($r->reportable)<a href="{{ route('posts.show', $r->reportable) }}" class="text-xs font-bold text-brand-600">ver post →</a>@endif
                </div>
            @empty
                <p class="text-sm text-stone-500">Nenhuma denúncia aberta 🎉</p>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
