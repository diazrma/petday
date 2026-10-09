@props(['post'])
@php($mood = $post->moodInfo())
@php($type = $post->typeInfo())
<article class="card overflow-hidden" x-data="doubleTapPaw">
    <header class="flex items-center gap-3 p-4">
        <a href="{{ route('pets.show', $post->pet) }}"><x-pet-avatar :pet="$post->pet" size="md" /></a>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2">
                <a href="{{ route('pets.show', $post->pet) }}" class="font-display text-lg font-semibold leading-tight hover:text-brand-600">{{ $post->pet->name }}</a>
                @if ($mood)
                    <span class="text-sm text-stone-500">está se sentindo <b style="color: {{ $mood['color'] }}">{{ $mood['emoji'] }} {{ mb_strtolower($mood['label']) }}</b></span>
                @endif
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-stone-400">
                <a href="{{ route('pets.day', [$post->pet, $post->diary_date->toDateString()]) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-brand-700 hover:bg-brand-100" title="Ver este dia no diário">
                    📅 {{ $post->diary_date->isToday() ? 'Hoje' : $post->diary_date->format('d/m/Y') }}
                </a>
                <span>{{ $type['emoji'] }} {{ $type['label'] }}</span>
                @if ($post->location)<span class="truncate">📍 {{ $post->location }}</span>@endif
            </div>
        </div>
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="btn-ghost !p-2" aria-label="Mais opções">⋯</button>
            <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-2xl bg-surface py-1 text-sm shadow-xl ring-1 ring-black/5">
                <a href="{{ route('posts.show', $post) }}" class="block px-4 py-2 hover:bg-stone-50">Abrir post</a>
                @if (auth()->id() === $post->user_id || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('posts.destroy', $post) }}" data-confirm-title="Excluir este post?" data-confirm="A foto, as patinhas e os comentários serão apagados." data-confirm-button="Excluir post" data-confirm-icon="🗑️">
                        @csrf @method('DELETE')
                        <button class="block w-full px-4 py-2 text-left text-rose-600 hover:bg-rose-50">Excluir</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('posts.report', $post) }}" onsubmit="const r = prompt('Motivo da denúncia:'); if (!r) return false; this.reason.value = r;">
                        @csrf <input type="hidden" name="reason">
                        <button class="block w-full px-4 py-2 text-left text-rose-600 hover:bg-rose-50">Denunciar</button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    @if ($post->imageUrl())
        <div class="relative select-none bg-stone-100" @dblclick="tap()">
            <img src="{{ $post->imageUrl() }}" alt="Foto de {{ $post->pet->name }}" loading="lazy" class="max-h-[560px] w-full object-cover">
            <div x-cloak x-show="showBig" x-transition.scale.origin.center class="pointer-events-none absolute inset-0 flex items-center justify-center text-8xl drop-shadow-xl">🐾</div>
        </div>
    @elseif ($post->body && mb_strlen($post->body) < 140)
        <div class="relative mx-4 flex min-h-40 items-center justify-center rounded-3xl p-8 text-center select-none" style="background: linear-gradient(135deg, {{ $post->pet->color }}, {{ $mood['color'] ?? '#fb923c' }})" @dblclick="tap()">
            <p class="font-display text-2xl font-semibold text-white drop-shadow">{{ $post->body }}</p>
            <div x-cloak x-show="showBig" x-transition.scale class="pointer-events-none absolute inset-0 flex items-center justify-center text-8xl">🐾</div>
        </div>
    @endif

    @if ($post->body && ($post->imageUrl() || mb_strlen($post->body) >= 140))
        <p class="whitespace-pre-line px-4 pt-3 text-[15px] leading-relaxed"><a href="{{ route('pets.show', $post->pet) }}" class="font-extrabold">{{ $post->pet->name }}</a> {{ $post->body }}</p>
    @endif

    <footer class="flex items-center gap-2 p-4">
        <x-paw-button :post="$post" />
        <a href="{{ route('posts.show', $post) }}#comentarios" class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-3 py-1.5 text-sm font-extrabold text-stone-600 hover:bg-stone-200">
            💬 {{ $post->comments_count }}
        </a>
        <span class="ml-auto text-xs font-semibold text-stone-400">{{ $post->created_at->diffForHumans() }}</span>
    </footer>

    @if ($post->relationLoaded('comments') && $post->comments->isNotEmpty())
        <div class="space-y-1 border-t border-stone-100 px-4 py-3 text-sm">
            @foreach ($post->comments->reverse() as $comment)
                <p><b>{{ $comment->pet?->name ?? $comment->user->name }}</b> <span class="text-stone-600">{{ $comment->body }}</span></p>
            @endforeach
        </div>
    @endif
</article>
