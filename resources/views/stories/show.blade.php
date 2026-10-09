@php use App\Support\Catalog; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rastros de {{ $pet->name }} · PetDay</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-hidden bg-black font-sans text-white">
<div class="relative h-dvh"
     x-data="rastroViewer({
        items: @js($payload),
        nextUrl: @js($next ? route('stories.show', $next) : null),
        closeUrl: @js(route('feed')),
        reactUrl: @js(route('stories.react', '__ID__')),
        commentUrl: @js(route('stories.comments.store', '__ID__')),
        reactions: @js(Catalog::STORY_REACTIONS),
     })"
     @keydown.right.window="drawer || next()" @keydown.left.window="drawer || prev()"
     @keydown.escape.window="drawer ? drawer = false : window.location = closeUrl">

    {{-- Ambiente: a própria foto desfocada + brilho na cor do humor --}}
    @foreach ($stories as $i => $story)
        <div x-show="index === {{ $i }}" x-transition.opacity.duration.500ms class="absolute inset-0">
            @if ($story->imageUrl())
                <img src="{{ $story->imageUrl() }}" class="absolute inset-0 size-full scale-125 object-cover opacity-50 blur-3xl" alt="">
            @endif
            <div class="absolute inset-0" style="background: radial-gradient(circle at 50% 80%, {{ $story->moodInfo()['color'] ?? $pet->color }}66, transparent 65%)"></div>
        </div>
    @endforeach

    <div class="relative mx-auto flex h-full max-w-md flex-col sm:py-4" :class="leaving ? 'rastro-leave' : 'rastro-enter'">
        <div class="relative flex-1 overflow-hidden shadow-2xl sm:rounded-[2rem]">

            {{-- Rastros: trilho horizontal, cada um é uma polaroid que passa de lado --}}
            <div x-ref="stage" class="absolute inset-0 touch-none select-none"
                 @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="cancel()" @contextmenu.prevent>
                <div class="flex h-full will-change-transform" :style="trackStyle()">
                    @foreach ($stories as $i => $story)
                        <div class="relative h-full w-full shrink-0 overflow-hidden bg-gradient-to-br {{ $story->background }}" :style="slideStyle({{ $i }})">
                            @if ($story->imageUrl())
                                <img src="{{ $story->imageUrl() }}" class="pointer-events-none absolute inset-0 size-full object-cover" alt="" draggable="false">
                            @endif
                            {{-- Legenda + sticker no lugar que o tutor escolheu --}}
                            @if ($story->caption || $story->sticker)
                                @php $pos = $story->captionPosition(); @endphp
                                <div class="pointer-events-none absolute flex w-max max-w-[85%] gap-2 {{ $story->imageUrl() ? 'items-center' : 'flex-col items-center text-center' }}"
                                     style="left: {{ $pos['x'] }}%; top: {{ $pos['y'] }}%; transform: translate(-50%, -50%)">
                                    @if ($story->sticker)<span class="rastro-float {{ $story->imageUrl() ? 'text-5xl' : 'text-7xl' }} drop-shadow-lg">{{ $story->sticker }}</span>@endif
                                    @if ($story->caption)
                                        <p class="font-display font-semibold {{ $story->imageUrl() ? '-rotate-2 rounded-xl bg-black/55 px-3 py-1.5 text-2xl text-white shadow-xl ring-1 ring-white/20 backdrop-blur-md' : 'text-3xl text-white drop-shadow' }}">{{ $story->caption }}</p>
                                    @endif
                                </div>
                            @endif
                            {{-- escurece a polaroid que está saindo/entrando --}}
                            <div class="pointer-events-none absolute inset-0 bg-black" :style="`opacity: ${Math.min(Math.abs(offset({{ $i }})), 1) * .45}`"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Cabeçalho --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 bg-gradient-to-b from-black/60 to-transparent px-4 pt-[max(.75rem,env(safe-area-inset-top))] pb-10 transition-opacity" :class="dragging && 'opacity-0'">
                <div class="pointer-events-auto flex items-center gap-3">
                    <a href="{{ route('pets.show', $pet) }}"><x-pet-avatar :pet="$pet" size="sm" /></a>
                    <div class="min-w-0 flex-1 leading-tight">
                        <a href="{{ route('pets.show', $pet) }}" class="font-display text-lg font-semibold drop-shadow">{{ $pet->name }}</a>
                        @foreach ($stories as $i => $story)
                            @php $hoursLeft = (int) floor(now()->diffInHours($story->expires_at)); @endphp
                            <p x-show="index === {{ $i }}" class="truncate text-xs font-bold text-white/80">
                                @if ($m = $story->moodInfo()){{ $m['emoji'] }} {{ $m['label'] }} · @endif
                                {{ $story->created_at->diffForHumans(null, true, true) }} · ⏳ {{ $hoursLeft < 1 ? '<1h' : $hoursLeft.'h' }}
                            </p>
                        @endforeach
                    </div>
                    @if ($pet->user_id === auth()->id())
                        @foreach ($stories as $i => $story)
                            <form x-show="index === {{ $i }}" method="POST" action="{{ route('stories.destroy', $story) }}" data-confirm-title="Apagar este rastro?" data-confirm="Ele some para todo mundo, junto com os recados." data-confirm-button="Apagar" data-confirm-icon="🗑️">@csrf @method('DELETE')<button class="flex size-9 items-center justify-center rounded-full bg-white/15 text-sm backdrop-blur" aria-label="Apagar rastro">🗑️</button></form>
                        @endforeach
                    @endif
                    <a href="{{ route('feed') }}" class="flex size-9 items-center justify-center rounded-full bg-white/15 text-lg backdrop-blur" aria-label="Fechar">✕</a>
                </div>
            </div>

            {{-- Farejando (pausado) --}}
            <div x-show="holding" x-transition.opacity class="pointer-events-none absolute inset-x-0 top-24 z-10 flex justify-center">
                <span class="rounded-full bg-black/50 px-4 py-1.5 text-sm font-extrabold backdrop-blur">👃 farejando…</span>
            </div>

            {{-- Chuva de reações --}}
            <div x-ref="rain" class="pointer-events-none absolute inset-0 z-20 overflow-hidden"></div>

            {{-- Rodapé: recados flutuantes, trilha de patinhas, reações --}}
            <div class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black/80 via-black/40 to-transparent px-4 pt-16 pb-[max(1rem,env(safe-area-inset-bottom))] transition-opacity" :class="dragging && 'opacity-0 pointer-events-none'">

                {{-- Recados: balões que vão se revezando, para todo mundo ver todos --}}
                <div class="mb-3">
                    <button type="button" @click="openDrawer()" class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-extrabold backdrop-blur-md"
                            x-show="cur.comments.length" x-text="`💬 ${cur.comments.length} ${cur.comments.length === 1 ? 'recado' : 'recados'} · ver todos`"></button>
                    <div class="flex min-h-[5rem] flex-col items-start justify-end gap-1.5" @click="openDrawer()">
                        <template x-for="c in visibleComments()" :key="c.id">
                            <div class="rastro-bubble flex max-w-[88%] cursor-pointer items-start gap-2 rounded-2xl rounded-bl-md bg-black/65 py-2 pr-3.5 pl-2 text-white shadow-xl ring-1 ring-white/15 backdrop-blur-md">
                                <span class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-sm ring-2 ring-white/70">
                                    <template x-if="c.avatar"><img :src="c.avatar" class="size-full object-cover" alt=""></template>
                                    <template x-if="!c.avatar"><span x-text="c.emoji"></span></template>
                                </span>
                                <span class="min-w-0 leading-snug">
                                    <b class="block text-xs font-extrabold text-brand-300" x-text="c.author"></b>
                                    <span class="line-clamp-2 text-[15px] font-semibold" x-text="c.body"></span>
                                </span>
                            </div>
                        </template>
                        <div x-show="!cur.comments.length" class="rastro-bubble cursor-pointer rounded-2xl rounded-bl-md bg-black/50 px-3.5 py-2 text-sm font-bold text-white/90 ring-1 ring-white/15 backdrop-blur-md">
                            💬 Seja o primeiro a deixar um recado 🐾
                        </div>
                    </div>
                </div>

                {{-- Trilha de patinhas: a atual vai sendo "pisada", as vistas desbotam, a próxima espera --}}
                <div class="mb-4 flex items-end justify-center gap-3" aria-hidden="true">
                    <template x-for="(item, i) in items" :key="item.id">
                        <button type="button" @click="go(i)" class="relative size-9 shrink-0"
                                :style="`transform: translateY(${i % 2 ? -8 : 0}px) rotate(${i % 2 ? 16 : -16}deg) scale(${i === index ? 1.3 : 0.9}); opacity: ${i < index ? 0.4 : i === index ? 1 : 0.55}; transition: transform .45s cubic-bezier(.3,1.5,.5,1), opacity .6s`">
                            <svg viewBox="0 0 24 24" class="absolute inset-0 size-full fill-white/40"><use href="#paw" /></svg>
                            <svg viewBox="0 0 24 24" class="absolute inset-0 size-full fill-white drop-shadow-[0_0_6px_rgba(255,255,255,.6)]"
                                 :style="`clip-path: inset(${(1 - fill(i)) * 100}% 0 0 0)`"><use href="#paw" /></svg>
                        </button>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="openDrawer(true)" class="flex min-w-0 flex-1 items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2.5 text-left text-sm text-white/80 backdrop-blur">
                        💬 <span class="truncate">Escrever recado…</span>
                        <span class="ml-auto shrink-0 text-xs font-extrabold" x-show="cur.comments.length" x-text="cur.comments.length"></span>
                    </button>
                    <template x-for="(r, kind) in reactions" :key="kind">
                        <button type="button" @click="react(kind, $event)" :aria-label="r.label"
                                class="relative flex size-11 shrink-0 items-center justify-center rounded-full text-xl transition active:scale-90"
                                :class="cur.mine === kind ? 'bg-white shadow-lg scale-110' : 'bg-white/15 backdrop-blur'">
                            <span x-text="r.emoji"></span>
                            <span x-show="cur.counts[kind]" x-text="cur.counts[kind]" class="absolute -top-1 -right-1 min-w-5 rounded-full bg-brand-500 px-1 text-center text-[10px] font-extrabold leading-5"></span>
                        </button>
                    </template>
                </div>

                @if ($pet->user_id === auth()->id())
                    @foreach ($stories as $i => $story)
                        <p x-show="index === {{ $i }}" class="mt-2 text-center text-xs font-bold text-white/70">
                            👀 {{ $story->views_count }} {{ $story->views_count === 1 ? 'pet farejou' : 'farejaram' }} este rastro
                        </p>
                    @endforeach
                @endif
            </div>

            {{-- Gaveta de recados --}}
            <div x-show="drawer" x-cloak class="absolute inset-0 z-30 flex items-end">
                <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-black/50" @click="drawer = false"></div>
                <div x-show="drawer" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:leave="transition duration-200" x-transition:leave-end="translate-y-full"
                     class="relative flex max-h-[75%] w-full flex-col rounded-t-[2rem] bg-surface text-ink">
                    <div class="flex items-center justify-between px-5 pt-4 pb-2">
                        <h2 class="font-display text-xl font-semibold">💬 Recados <span class="text-stone-400" x-text="cur.comments.length"></span></h2>
                        <button type="button" @click="drawer = false" class="btn-ghost !p-2" aria-label="Fechar">✕</button>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto px-5 py-2" x-ref="list">
                        <template x-for="c in cur.comments" :key="c.id">
                            <div class="flex items-start gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-50">
                                    <template x-if="c.avatar"><img :src="c.avatar" class="size-full object-cover" alt=""></template>
                                    <template x-if="!c.avatar"><span x-text="c.emoji"></span></template>
                                </span>
                                <div class="min-w-0 flex-1 rounded-2xl rounded-tl-md bg-stone-100 px-3 py-2 text-sm">
                                    <p><b x-text="c.author"></b> <span class="text-xs text-stone-400" x-text="c.ago"></span></p>
                                    <p class="break-words" x-text="c.body"></p>
                                </div>
                                <button type="button" x-show="c.canDelete" @click="removeComment(c)" class="pt-2 text-xs text-stone-400 hover:text-rose-600" aria-label="Apagar recado">🗑️</button>
                            </div>
                        </template>
                        <p x-show="!cur.comments.length" class="py-8 text-center text-sm text-stone-500">Nenhum recado ainda. Seja o primeiro a farejar por aqui 🐾</p>
                    </div>
                    <form @submit.prevent="sendComment()" class="flex gap-2 border-t border-stone-100 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <input x-ref="input" x-model="draft" maxlength="300" class="input !rounded-full" placeholder="Escreva um recado para {{ $pet->name }}…">
                        <button class="btn-primary !rounded-full" :disabled="sending || !draft.trim()">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <svg class="hidden"><symbol id="paw" viewBox="0 0 24 24"><path d="M3.8,9.5a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M7.8,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M12.3,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M16.1,9.7a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M12.2,11.2c-2.9,0 -6.4,3.7 -6.4,6.5c0,1.8 1.4,2.8 3,2.8c1.3,0 2.2,-0.7 3.4,-0.7s2.1,0.7 3.4,0.7c1.6,0 3,-1 3,-2.8c0,-2.8 -3.5,-6.5 -6.4,-6.5z"/></symbol></svg>
</div>
</body>
</html>
