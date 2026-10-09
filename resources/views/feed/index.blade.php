<x-layouts.app title="Início">
<div class="flex gap-6">
    <div class="mx-auto w-full max-w-xl space-y-5">
        {{-- Rastros: polaroids que desbotam em 24h --}}
        <section aria-label="Rastros">
            <h2 class="mb-2 flex items-baseline gap-2 font-display text-lg font-semibold"><svg viewBox="0 0 24 24" class="size-5 self-center fill-brand-500"><path d="M3.8,9.5a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M7.8,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M12.3,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M16.1,9.7a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M12.2,11.2c-2.9,0 -6.4,3.7 -6.4,6.5c0,1.8 1.4,2.8 3,2.8c1.3,0 2.2,-0.7 3.4,-0.7s2.1,0.7 3.4,0.7c1.6,0 3,-1 3,-2.8c0,-2.8 -3.5,-6.5 -6.4,-6.5z"/></svg> Rastros de hoje <span class="text-xs font-bold text-stone-400">somem em 24h</span></h2>
            <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pt-2 pb-3 lg:mx-0 lg:px-1">
                <button @click="$dispatch('open-composer', { tab: 'story' })" class="flex h-40 w-28 shrink-0 -rotate-2 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-brand-300 bg-surface p-2 text-center transition hover:rotate-0">
                    <span class="flex size-11 items-center justify-center rounded-full bg-brand-500 text-2xl text-white shadow-lg shadow-brand-500/30">＋</span>
                    <span class="text-xs font-extrabold text-brand-700">Deixar um rastro</span>
                </button>
                @foreach ($stories as $i => $s)
                    @php $latest = $s['latest']; $mood = $latest->moodInfo(); @endphp
                    <a href="{{ route('stories.show', $s['pet']) }}"
                       class="group relative w-28 shrink-0 rounded-2xl bg-surface p-1.5 pb-2 shadow-lg ring-1 ring-orange-900/5 transition hover:z-10 hover:rotate-0 hover:scale-105 {{ $i % 2 ? 'rotate-2' : '-rotate-1' }} {{ $s['allSeen'] ? 'opacity-60 saturate-50' : '' }}">
                        <span class="relative block h-28 overflow-hidden rounded-xl bg-gradient-to-br {{ $latest->background }}">
                            @if ($latest->imageUrl())
                                <img src="{{ $latest->imageUrl() }}" class="size-full object-cover" alt="" loading="lazy">
                            @else
                                <span class="flex size-full items-center justify-center p-2 text-center text-3xl">{{ $latest->sticker ?: $s['pet']->emoji() }}</span>
                            @endif
                            @if ($s['count'] > 1)<span class="absolute top-1.5 left-1.5 rounded-full bg-black/50 px-1.5 text-[10px] font-extrabold text-white backdrop-blur">{{ $s['count'] }} 🐾</span>@endif
                            @if ($mood)<span class="absolute top-1 right-1 text-lg drop-shadow">{{ $mood['emoji'] }}</span>@endif
                            @unless ($s['allSeen'])<span class="rastro-pulse absolute right-1.5 bottom-1.5 size-3 rounded-full bg-brand-500 ring-2 ring-white"></span>@endunless
                        </span>
                        <span class="mt-1.5 flex items-center gap-1.5 px-0.5">
                            <x-pet-avatar :pet="$s['pet']" size="xs" />
                            <span class="min-w-0 flex-1 truncate text-xs font-extrabold">{{ $s['pet']->name }}</span>
                            {{-- pegada que desbota com o tempo --}}
                            <svg viewBox="0 0 24 24" class="size-4 shrink-0 fill-brand-500" style="opacity: {{ 0.2 + 0.8 * $s['life'] }}" aria-label="tempo restante"><path d="M3.8,9.5a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M7.8,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M12.3,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M16.1,9.7a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M12.2,11.2c-2.9,0 -6.4,3.7 -6.4,6.5c0,1.8 1.4,2.8 3,2.8c1.3,0 2.2,-0.7 3.4,-0.7s2.1,0.7 3.4,0.7c1.6,0 3,-1 3,-2.8c0,-2.8 -3.5,-6.5 -6.4,-6.5z"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Aniversários --}}
        @foreach ($birthdays as $pet)
            <a href="{{ route('pets.show', $pet) }}" class="card flex items-center gap-4 bg-gradient-to-r from-pink-500 to-orange-400 p-4 text-white">
                <span class="text-4xl">🎂</span>
                <span class="flex-1"><b class="font-display text-lg">Hoje é aniversário de {{ $pet->name }}!</b><br><span class="text-sm text-white/90">Deixe uma patinha de parabéns 🐾</span></span>
                <x-pet-avatar :pet="$pet" size="md" />
            </a>
        @endforeach

        {{-- Composer rápido --}}
        <button @click="$dispatch('open-composer')" class="card flex w-full items-center gap-3 p-4 text-left">
            <span class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-xl">📅</span>
            <span class="flex-1 text-stone-400">Como foi o dia hoje?</span>
            <span class="chip bg-brand-50 text-brand-700">📷 Foto</span>
            <span class="chip bg-stone-100 text-stone-600">😄 Humor</span>
        </button>

        {{-- Memórias --}}
        @if ($memories->isNotEmpty())
            <section class="card overflow-hidden">
                <h2 class="flex items-center gap-2 px-4 pt-4 font-display text-lg font-semibold">🕰️ Neste dia</h2>
                <div class="flex gap-3 overflow-x-auto p-4">
                    @foreach ($memories as $m)
                        <a href="{{ route('posts.show', $m) }}" class="relative h-36 w-28 shrink-0 overflow-hidden rounded-2xl" style="background: {{ $m->pet->color }}">
                            @if ($m->imageUrl())<img src="{{ $m->imageUrl() }}" class="size-full object-cover">@else<span class="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-white">{{ \Illuminate\Support\Str::limit($m->body, 60) }}</span>@endif
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 p-2 text-xs font-bold text-white">{{ $m->pet->name }} · há {{ $m->diary_date->diffForHumans(null, true) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div data-infinite-items class="space-y-5">
        @forelse ($posts as $post)
            <x-post-card :post="$post" />
        @empty
            <div class="card p-10 text-center">
                <div class="text-6xl">🐕‍🦺</div>
                <h2 class="mt-3 font-display text-2xl font-semibold">Seu feed está quietinho</h2>
                <p class="mt-1 text-stone-500">Siga outros pets ou registre o primeiro dia do seu.</p>
                <a href="{{ route('explore') }}" class="btn-primary mt-5">Explorar pets</a>
            </div>
        @endforelse
        </div>

        <x-infinite-scroll :paginator="$posts" />
    </div>

    {{-- Lateral --}}
    <aside class="sticky top-6 hidden h-fit w-80 shrink-0 space-y-5 xl:block">
        <section class="card p-5">
            <h2 class="mb-3 flex items-center justify-between font-display text-lg font-semibold">🩺 Próximas consultas <a href="{{ route('appointments.index') }}" class="text-xs font-bold text-brand-600">ver todas</a></h2>
            @forelse ($upcoming as $a)
                <div class="flex items-center gap-3 border-t border-stone-100 py-2.5 first:border-0">
                    <div class="flex w-12 flex-col items-center rounded-xl bg-sky-50 py-1 text-sky-700">
                        <span class="text-[10px] font-extrabold uppercase">{{ $a->scheduled_at->locale('pt_BR')->translatedFormat('M') }}</span>
                        <span class="font-display text-xl font-bold leading-none">{{ $a->scheduled_at->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="truncate font-extrabold">{{ $a->pet->name }} · {{ $a->typeLabel() }}</p>
                        <p class="truncate text-stone-500">{{ $a->scheduled_at->format('H:i') }} · {{ $a->vet->clinic_name }}</p>
                    </div>
                    <span class="chip {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-500">Nenhuma consulta marcada.</p>
            @endforelse
        </section>

        @if ($dueSoon->isNotEmpty())
            <section class="card p-5">
                <h2 class="mb-3 font-display text-lg font-semibold">💉 Vencendo em breve</h2>
                @foreach ($dueSoon as $h)
                    <a href="{{ route('pets.show', [$h->pet, 'aba' => 'saude']) }}" class="flex items-center gap-3 py-2 text-sm">
                        <span class="text-xl">{{ $h->kindInfo()['emoji'] }}</span>
                        <span class="flex-1"><b>{{ $h->title }}</b> · {{ $h->pet->name }}</span>
                        <span class="font-bold text-amber-600">{{ $h->next_due_on->format('d/m') }}</span>
                    </a>
                @endforeach
            </section>
        @endif

        <section class="card p-5">
            <h2 class="mb-3 font-display text-lg font-semibold">✨ Pets para seguir</h2>
            @foreach ($suggestions as $pet)
                <div class="flex items-center gap-3 py-2">
                    <a href="{{ route('pets.show', $pet) }}"><x-pet-avatar :pet="$pet" size="sm" /></a>
                    <a href="{{ route('pets.show', $pet) }}" class="min-w-0 flex-1 text-sm">
                        <p class="truncate font-extrabold">{{ $pet->name }}</p>
                        <p class="truncate text-stone-500">{{ $pet->breed ?: $pet->speciesInfo()['label'] }} · {{ $pet->followers_count }} seguidores</p>
                    </a>
                    <form method="POST" action="{{ route('pets.follow', $pet) }}">@csrf<button class="btn-soft !px-3 !py-1.5 text-xs">Seguir</button></form>
                </div>
            @endforeach
        </section>
    </aside>
</div>
</x-layouts.app>
