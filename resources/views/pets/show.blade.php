@php use App\Support\Catalog; @endphp
<x-layouts.app :title="$pet->name">
<div class="mx-auto max-w-5xl">
    {{-- Capa + cabeçalho --}}
    <section class="card overflow-hidden">
        <div class="relative h-40 sm:h-56" style="background: linear-gradient(135deg, {{ $pet->color }}, {{ $pet->color }}88)">
            @if ($pet->coverUrl())<img src="{{ $pet->coverUrl() }}" class="size-full object-cover" alt="">@else
                <div class="absolute inset-0 overflow-hidden opacity-20 text-5xl leading-[4rem] tracking-[2rem] select-none" aria-hidden="true">{{ str_repeat('🐾 '.$pet->emoji().' ', 40) }}</div>
            @endif
        </div>
        <div class="px-5 pb-5 sm:px-8">
            <div class="-mt-14 flex flex-col gap-4 sm:-mt-16 sm:flex-row sm:items-end">
                @if ($hasStories)
                    <a href="{{ route('stories.show', $pet) }}" title="Ver stories"><x-pet-avatar :pet="$pet" size="xl" ring /></a>
                @else
                    <x-pet-avatar :pet="$pet" size="xl" class="rounded-full bg-surface p-1" />
                @endif
                <div class="min-w-0 flex-1 sm:pb-2">
                    <h1 class="flex flex-wrap items-center gap-2 font-display text-4xl font-bold">
                        {{ $pet->name }}
                        @if ($pet->gender)<span class="text-2xl {{ $pet->gender === 'male' ? 'text-sky-500' : 'text-pink-500' }}">{{ $pet->gender === 'male' ? '♂' : '♀' }}</span>@endif
                        @if ($pet->isBirthdayToday())<span class="chip bg-pink-100 text-pink-700 text-sm">🎂 Aniversário hoje!</span>@endif
                    </h1>
                    <p class="font-semibold text-stone-500">
                        {{ $pet->emoji() }} {{ $pet->breed ?: $pet->speciesInfo()['label'] }}
                        @if ($pet->ageLabel()) · {{ $pet->ageLabel() }}@endif
                        @if ($pet->weight) · {{ rtrim(rtrim(number_format($pet->weight, 2, ',', ''), '0'), ',') }} kg @endif
                        · tutor <b>{{ '@'.$pet->owner->username }}</b>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 sm:pb-2">
                    @if ($isOwner)
                        <a href="{{ route('appointments.create', $pet) }}" class="btn-primary">🩺 Agendar consulta</a>
                        <a href="{{ route('pets.edit', $pet) }}" class="btn-soft">Editar</a>
                    @else
                        <form method="POST" action="{{ route('pets.follow', $pet) }}">@csrf
                            <button class="{{ $following ? 'btn-soft' : 'btn-primary' }}">{{ $following ? '✓ Seguindo' : '＋ Seguir' }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($pet->bio)<p class="mt-4 max-w-2xl whitespace-pre-line">{{ $pet->bio }}</p>@endif
            @if ($pet->personality)
                <div class="mt-3 flex flex-wrap gap-1.5">@foreach ($pet->personality as $trait)<span class="chip bg-stone-100 text-stone-600">{{ $trait }}</span>@endforeach</div>
            @endif

            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-stone-50 p-3"><dt class="text-xs font-extrabold uppercase text-stone-400">Dias no diário</dt><dd class="font-display text-2xl font-bold">{{ $pet->posts_count }}</dd></div>
                <div class="rounded-2xl bg-brand-50 p-3"><dt class="text-xs font-extrabold uppercase text-brand-600/70">Patinhas</dt><dd class="font-display text-2xl font-bold text-brand-600">🐾 {{ $totalPaws }}</dd></div>
                <div class="rounded-2xl bg-stone-50 p-3"><dt class="text-xs font-extrabold uppercase text-stone-400">Seguidores</dt><dd class="font-display text-2xl font-bold">{{ $pet->followers_count }}</dd></div>
                <div class="rounded-2xl bg-amber-50 p-3"><dt class="text-xs font-extrabold uppercase text-amber-600/70">Sequência</dt><dd class="font-display text-2xl font-bold text-amber-600">🔥 {{ $streak }} {{ $streak === 1 ? 'dia' : 'dias' }}</dd></div>
            </dl>
        </div>

        <nav class="flex border-t border-stone-100 px-3 sm:px-6">
            @foreach (['diario' => '📅 Diário', 'posts' => '🖼️ Posts', 'saude' => '🩺 Saúde'] as $key => $label)
                <a href="{{ route('pets.show', [$pet, 'aba' => $key]) }}" class="-mb-px border-b-[3px] px-4 py-3.5 font-extrabold {{ $tab === $key ? 'border-brand-500 text-brand-600' : 'border-transparent text-stone-500 hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    {{-- Aba: Diário (calendário) --}}
    @if ($tab === 'diario')
        <section class="card mt-5 p-4 sm:p-6">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <a href="{{ route('pets.show', [$pet, 'mes' => $calendar->prev()]) }}" class="btn-ghost !p-2.5" aria-label="Mês anterior">←</a>
                <h2 class="min-w-44 text-center font-display text-2xl font-semibold">{{ $calendar->label() }}</h2>
                <a href="{{ route('pets.show', [$pet, 'mes' => $calendar->next()]) }}" class="btn-ghost !p-2.5" aria-label="Próximo mês">→</a>
                @unless ($calendar->month->isSameMonth(now()))
                    <a href="{{ route('pets.show', $pet) }}" class="btn-soft !py-1.5 text-xs">Hoje</a>
                @endunless
                <div class="ml-auto flex flex-wrap gap-2 text-xs font-bold text-stone-500">
                    <span class="chip bg-stone-100">📸 {{ $summary['days'] }} dias registrados</span>
                    <span class="chip bg-brand-50 text-brand-700">🐾 {{ $summary['paws'] }} patinhas</span>
                    @if ($summary['topMood'])<span class="chip bg-stone-100">Humor do mês: {{ $summary['topMood']['emoji'] }} {{ $summary['topMood']['label'] }}</span>@endif
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-extrabold uppercase text-stone-400 sm:gap-2">
                @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $d)<div class="py-1">{{ $d }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        @php
                            $hasContent = $day['posts']->isNotEmpty() || $day['appointments']->isNotEmpty() || $day['health']->isNotEmpty() || $day['birthday'];
                        @endphp
                        <a href="{{ $hasContent ? route('pets.day', [$pet, $day['date']->toDateString()]) : '#' }}"
                           @if (! $hasContent && $isOwner && ! $day['isFuture']) @click.prevent="$dispatch('open-composer'); $nextTick(() => document.getElementById('diary_date').value = '{{ $day['date']->toDateString() }}')" @elseif (! $hasContent) @click.prevent @endif
                           class="group relative aspect-square overflow-hidden rounded-xl sm:rounded-2xl transition
                                  {{ $day['inMonth'] ? '' : 'opacity-35' }}
                                  {{ $day['cover'] ? '' : ($day['posts']->isNotEmpty() ? '' : 'bg-stone-50') }}
                                  {{ $day['isToday'] ? 'ring-[3px] ring-brand-500 ring-offset-2' : '' }}
                                  {{ $hasContent || ($isOwner && ! $day['isFuture']) ? 'hover:scale-[1.04] hover:shadow-lg hover:z-10' : 'cursor-default' }}"
                           @if (! $day['cover'] && $day['posts']->isNotEmpty()) style="background: {{ $day['mood']['color'] ?? $pet->color }}33" @endif
                           title="{{ $day['date']->format('d/m/Y') }}">
                            @if ($day['cover'])
                                <img src="{{ $day['cover']->imageUrl() }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                                <span class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/40"></span>
                            @endif
                            <span class="absolute left-1.5 top-1 text-xs font-extrabold sm:left-2 sm:top-1.5 sm:text-sm {{ $day['cover'] ? 'text-white' : ($day['isToday'] ? 'text-brand-600' : 'text-stone-500') }}">{{ $day['date']->day }}</span>

                            @if (! $day['cover'] && $day['mood'])
                                <span class="absolute inset-0 flex items-center justify-center text-xl sm:text-3xl">{{ $day['mood']['emoji'] }}</span>
                            @elseif (! $day['cover'] && $day['posts']->isNotEmpty())
                                <span class="absolute inset-0 flex items-center justify-center text-xl sm:text-3xl">{{ $day['posts']->first()->typeInfo()['emoji'] }}</span>
                            @elseif (! $hasContent && $isOwner && ! $day['isFuture'] && $day['inMonth'])
                                <span class="absolute inset-0 flex items-center justify-center text-xl text-stone-300 opacity-0 transition group-hover:opacity-100">＋</span>
                            @endif

                            <span class="absolute inset-x-1 bottom-1 flex items-center justify-center gap-0.5 text-[10px] sm:text-xs">
                                @if ($day['birthday'])<span title="Aniversário">🎂</span>@endif
                                @foreach ($day['appointments'] as $a)<span title="Consulta {{ $a->scheduled_at->format('H:i') }}" class="rounded-full bg-sky-500 px-1 text-white">🩺</span>@endforeach
                                @foreach ($day['health'] as $h)<span title="{{ $h->title }}">{{ $h->kindInfo()['emoji'] }}</span>@endforeach
                                @if ($day['posts']->count() > 1)<span class="rounded-full bg-black/50 px-1.5 font-bold text-white">+{{ $day['posts']->count() - 1 }}</span>@endif
                                @if ($day['paws'] > 0)<span class="hidden rounded-full bg-surface/90 px-1.5 font-extrabold text-brand-600 sm:inline">🐾{{ $day['paws'] }}</span>@endif
                            </span>
                        </a>
                    @endforeach
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-stone-500">
                <span>🩺 Consulta veterinária</span><span>💉 Vacina / lembrete de saúde</span><span>🎂 Aniversário</span><span>🐾 Patinhas do dia</span>
                @if ($isOwner)<span class="text-brand-600">Toque num dia vazio para registrá-lo</span>@endif
            </div>
        </section>
    @endif

    {{-- Aba: Posts --}}
    @if ($tab === 'posts')
        <section class="mt-5">
            @if ($posts->isEmpty())
                <div class="card p-10 text-center text-stone-500">Nenhum post ainda.</div>
            @else
                <div data-infinite-items class="grid grid-cols-3 gap-1 sm:gap-3">
                    @foreach ($posts as $post)
                        <a href="{{ route('posts.show', $post) }}" class="group relative aspect-square overflow-hidden rounded-xl sm:rounded-2xl" style="background: {{ $post->moodInfo()['color'] ?? $pet->color }}">
                            @if ($post->imageUrl())
                                <img src="{{ $post->imageUrl() }}" loading="lazy" class="size-full object-cover transition group-hover:scale-105" alt="">
                            @else
                                <span class="flex size-full items-center justify-center p-3 text-center text-sm font-bold text-white">{{ \Illuminate\Support\Str::limit($post->body, 80) }}</span>
                            @endif
                            <span class="absolute inset-0 flex items-center justify-center gap-4 bg-black/40 font-extrabold text-white opacity-0 transition group-hover:opacity-100">🐾 {{ $post->paws_count }} 💬 {{ $post->comments_count }}</span>
                            <span class="absolute left-2 top-2 rounded-full bg-black/40 px-2 py-0.5 text-[10px] font-bold text-white">{{ $post->diary_date->format('d/m') }}</span>
                        </a>
                    @endforeach
                </div>
                <x-infinite-scroll :paginator="$posts" />
            @endif
        </section>
    @endif

    {{-- Aba: Saúde --}}
    @if ($tab === 'saude')
        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <section class="card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">🩺 Consultas</h2>
                    @if ($isOwner)<a href="{{ route('appointments.create', $pet) }}" class="btn-soft !py-1.5 text-xs">＋ Agendar</a>@endif
                </div>
                @forelse ($appointments as $a)
                    <div class="flex items-start gap-3 border-t border-stone-100 py-3 first:border-0">
                        <div class="flex w-14 shrink-0 flex-col items-center rounded-2xl bg-sky-50 py-1.5 text-sky-700">
                            <span class="text-[10px] font-extrabold uppercase">{{ $a->scheduled_at->locale('pt_BR')->translatedFormat('M') }}</span>
                            <span class="font-display text-2xl font-bold leading-none">{{ $a->scheduled_at->format('d') }}</span>
                            <span class="text-[10px] font-bold">{{ $a->scheduled_at->format('H:i') }}</span>
                        </div>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-extrabold">{{ $a->typeLabel() }} <span class="chip ml-1 {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span></p>
                            <p class="text-stone-500">{{ $a->vet->clinic_name }} · Dr(a). {{ $a->vet->name }}</p>
                            @if ($a->reason)<p class="mt-1 text-stone-600">“{{ $a->reason }}”</p>@endif
                            @if ($a->vet_notes)<p class="mt-2 rounded-xl bg-emerald-50 p-2 text-emerald-800"><b>Parecer:</b> {{ $a->vet_notes }}</p>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Nenhuma consulta registrada.</p>
                @endforelse
            </section>

            <section class="card p-5">
                <h2 class="mb-3 font-display text-xl font-semibold">💉 Carteirinha de saúde</h2>
                @forelse ($health as $h)
                    <div class="flex items-center gap-3 border-t border-stone-100 py-3 text-sm first:border-0">
                        <span class="flex size-10 items-center justify-center rounded-2xl bg-stone-50 text-xl">{{ $h->kindInfo()['emoji'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-extrabold">{{ $h->title }}</p>
                            <p class="text-stone-500">
                                @if ($h->applied_on)Aplicado em {{ $h->applied_on->format('d/m/Y') }}@endif
                                @if ($h->next_due_on) · Próxima: <b class="{{ $h->next_due_on->isPast() ? 'text-rose-600' : 'text-amber-600' }}">{{ $h->next_due_on->format('d/m/Y') }}</b>@endif
                            </p>
                        </div>
                        @if ($isOwner)
                            <form method="POST" action="{{ route('health.destroy', $h) }}" data-confirm-title="Remover registro?" data-confirm="Esse registro de saúde será apagado." data-confirm-button="Remover" data-confirm-icon="🩺">@csrf @method('DELETE')<button class="text-stone-300 hover:text-rose-500" aria-label="Remover">✕</button></form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Nenhum registro de saúde ainda.</p>
                @endforelse

                @if ($isOwner)
                    <form method="POST" action="{{ route('health.store', $pet) }}" class="mt-4 space-y-3 rounded-2xl bg-stone-50 p-4" x-data="{ open: false }">
                        @csrf
                        <button type="button" @click="open = !open" class="w-full text-left font-extrabold text-brand-600">＋ Adicionar vacina, vermífugo, exame…</button>
                        <div x-show="open" x-collapse class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <select name="kind" class="input bg-surface">@foreach (Catalog::HEALTH_KINDS as $k => $v)<option value="{{ $k }}">{{ $v['emoji'] }} {{ $v['label'] }}</option>@endforeach</select>
                                <input name="title" required maxlength="80" class="input bg-surface" placeholder="Ex.: V10, Antirrábica">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="label">Aplicado em</label><input type="date" name="applied_on" class="input bg-surface"></div>
                                <div><label class="label">Próxima dose</label><input type="date" name="next_due_on" class="input bg-surface"></div>
                            </div>
                            <input name="notes" maxlength="500" class="input bg-surface" placeholder="Observações (opcional)">
                            <button class="btn-primary w-full">Salvar registro</button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    @endif
</div>
</x-layouts.app>
