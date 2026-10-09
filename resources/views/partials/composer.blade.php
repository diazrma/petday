@php use App\Support\Catalog; @endphp
<div x-cloak x-show="composer" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" @keydown.escape.window="composer = false">
    <div x-show="composer" x-transition.opacity class="absolute inset-0 bg-night/50 backdrop-blur-sm" @click="composer = false"></div>

    <div x-show="composer" x-transition class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-[2rem] bg-surface p-5 shadow-2xl sm:rounded-[2rem]">
        @if (! $current)
            <div class="py-8 text-center">
                <div class="text-6xl">🐾</div>
                <h2 class="mt-3 font-display text-2xl font-semibold">Primeiro, cadastre seu pet</h2>
                <p class="mt-1 text-stone-500">No PetDay quem posta é o pet!</p>
                <a href="{{ route('pets.create') }}" class="btn-primary mt-5">Cadastrar pet</a>
            </div>
        @else
            <p class="mb-3 rounded-2xl bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700">🤖 Fotos passam por uma IA: o pet precisa aparecer (pessoas junto podem!).</p>
            <div class="mb-4 flex items-center gap-3">
                <x-pet-avatar :pet="$current" size="md" />
                <div class="flex-1">
                    <p class="text-xs font-bold text-stone-400">Postando como</p>
                    <p class="font-display text-xl font-semibold leading-tight">{{ $current->name }}</p>
                </div>
                <div class="flex rounded-2xl bg-stone-100 p-1 text-sm font-extrabold">
                    <button type="button" @click="composerTab = 'post'" class="rounded-xl px-3 py-1.5" :class="composerTab === 'post' ? 'bg-surface shadow' : 'text-stone-500'">📅 Diário</button>
                    <button type="button" @click="composerTab = 'story'" class="rounded-xl px-3 py-1.5" :class="composerTab === 'story' ? 'bg-surface shadow' : 'text-stone-500'">🐾 Rastro</button>
                </div>
            </div>

            {{-- Post no diário --}}
            <form x-show="composerTab === 'post'" method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="space-y-4"
                  x-data="{ type: 'moment', mood: null, busy: false, ...imagePreview() }" @submit="busy = true">
                @csrf
                <div>
                    <p class="label">O que aconteceu?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (Catalog::POST_TYPES as $key => $t)
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="{{ $key }}" x-model="type" class="peer sr-only">
                                <span class="chip bg-stone-100 text-stone-600 peer-checked:bg-brand-500 peer-checked:text-white">{{ $t['emoji'] }} {{ $t['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <textarea name="body" rows="3" maxlength="2000" class="input resize-none text-base" placeholder="Conte como foi o dia de {{ $current->name }}…"></textarea>

                <div>
                    <p class="label">Humor do dia</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach (Catalog::MOODS as $key => $m)
                            <label class="cursor-pointer" title="{{ $m['label'] }}">
                                <input type="radio" name="mood" value="{{ $key }}" x-model="mood" class="peer sr-only">
                                <span class="flex size-11 items-center justify-center rounded-2xl bg-stone-100 text-2xl transition peer-checked:scale-110 peer-checked:ring-2 peer-checked:ring-brand-500 peer-checked:bg-surface">{{ $m['emoji'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <template x-if="src">
                        <div class="relative"><img :src="src" class="max-h-64 w-full rounded-2xl object-cover"><button type="button" @click="clear()" class="absolute right-2 top-2 rounded-full bg-black/60 px-2.5 py-1 text-sm text-white">✕</button></div>
                    </template>
                    <label x-show="!src" class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-stone-200 py-6 font-bold text-stone-500 hover:border-brand-300 hover:bg-brand-50">
                        📷 Adicionar foto
                        <input x-ref="file" type="file" name="image" accept="image/*" class="sr-only" @change="pick">
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="diary_date">Dia no diário</label>
                        <input id="diary_date" type="date" name="diary_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="input">
                    </div>
                    <div>
                        <label class="label" for="location">Local</label>
                        <input id="location" type="text" name="location" maxlength="80" class="input" placeholder="Parque, casa…">
                    </div>
                </div>

                <p x-show="src" class="text-center text-xs font-semibold text-stone-400">🤖 Nossa IA confere se tem um pet na foto antes de publicar.</p>
                <button class="btn-primary w-full !py-3.5 text-base" :disabled="busy"><span x-show="!busy">Registrar no diário 📅</span><span x-show="busy" x-cloak>🤖 Verificando a foto…</span></button>
            </form>

            {{-- Rastro (some em 24h) --}}
            <form x-show="composerTab === 'story'" method="POST" action="{{ route('stories.store') }}" enctype="multipart/form-data" class="space-y-4"
                  x-data="{ bg: '{{ Catalog::STORY_BACKGROUNDS[0] }}', sticker: '', caption: '', mood: '', busy: false, ...imagePreview(), ...captionDrag() }" @submit="busy = true">
                @csrf
                <div x-ref="canvas" class="relative mx-auto aspect-[9/16] w-52 touch-none overflow-hidden rounded-3xl bg-gradient-to-br shadow-lg select-none" :class="bg">
                    <template x-if="src"><img :src="src" class="pointer-events-none absolute inset-0 size-full object-cover" draggable="false"></template>
                    <span x-show="mood" class="absolute top-2 left-2 rounded-full bg-black/40 px-2 py-0.5 text-xs font-bold text-white backdrop-blur" x-text="mood"></span>
                    {{-- Legenda + sticker: arraste para posicionar (mesmo visual do rastro) --}}
                    <div x-show="caption || sticker || !src" class="absolute flex w-max max-w-[85%] cursor-grab gap-1.5 active:cursor-grabbing"
                         :class="[src ? 'items-center' : 'flex-col items-center text-center', dragging && 'scale-105']"
                         :style="`left: ${pos().x}%; top: ${pos().y}%; transform: translate(-50%, -50%)`"
                         @pointerdown="startDrag($event)" @pointermove="drag($event)" @pointerup="endDrag()" @pointercancel="endDrag()">
                        <span :class="src ? 'text-3xl' : 'text-5xl'" x-text="sticker"></span>
                        <p x-show="caption || !src" :class="src ? '-rotate-2 rounded-lg bg-black/55 px-2 py-1 text-sm text-white shadow-xl ring-1 ring-white/20 backdrop-blur-md' : 'text-lg text-white drop-shadow'"
                           class="font-display font-semibold" x-text="caption || 'Seu rastro aqui'"></p>
                    </div>
                </div>
                <p x-show="caption || sticker" class="-mt-2 text-center text-xs font-bold text-stone-400">✋ Arraste a legenda na prévia para escolher onde ela fica <button type="button" x-show="moved" @click="resetPos()" class="text-brand-600 underline">centralizar</button></p>
                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-stone-100 py-3 font-bold text-stone-600 hover:bg-stone-200">
                    📷 <span x-text="src ? 'Trocar foto' : 'Escolher foto'"></span>
                    <input x-ref="file" type="file" name="image" accept="image/*" class="sr-only" @change="pick">
                </label>
                <input type="text" name="caption" x-model="caption" maxlength="200" class="input" placeholder="Legenda…">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (Catalog::STORY_BACKGROUNDS as $bg)
                        <button type="button" @click="bg = '{{ $bg }}'" class="size-8 rounded-full bg-gradient-to-br {{ $bg }}" :class="bg === '{{ $bg }}' && 'ring-2 ring-offset-2 ring-ink'" aria-label="Fundo"></button>
                    @endforeach
                    <span class="mx-1 h-6 w-px bg-stone-200"></span>
                    @foreach (['🦴', '🎾', '💤', '🎂', '❤️', '🛁', '🌳'] as $s)
                        <button type="button" @click="sticker = sticker === '{{ $s }}' ? '' : '{{ $s }}'" class="rounded-xl p-1 text-xl" :class="sticker === '{{ $s }}' && 'bg-brand-100'">{{ $s }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="background" :value="bg">
                <input type="hidden" name="sticker" :value="sticker">
                <input type="hidden" name="caption_x" :value="moved ? Math.round(x) : ''">
                <input type="hidden" name="caption_y" :value="moved ? Math.round(y) : ''">
                <div>
                    <p class="label">Como {{ $current->name }} está agora?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (Catalog::MOODS as $key => $m)
                            <label class="chip cursor-pointer bg-stone-100 text-stone-600 has-[:checked]:bg-brand-500 has-[:checked]:text-white">
                                <input type="radio" name="mood" value="{{ $key }}" class="sr-only" @change="mood = '{{ $m['emoji'] }} {{ $m['label'] }}'"> {{ $m['emoji'] }} {{ $m['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="btn-primary w-full !py-3.5 text-base" :disabled="busy"><span x-show="!busy">Deixar rastro 🐾</span><span x-show="busy" x-cloak>🤖 Verificando a foto…</span></button>
            </form>
        @endif
    </div>
</div>
