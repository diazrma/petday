<x-layouts.app :title="'Post de '.$post->pet->name">
<div class="mx-auto max-w-xl space-y-5">
    <a href="{{ url()->previous() }}" class="btn-ghost">← Voltar</a>
    @if ($post->hidden_at)<div class="rounded-2xl bg-amber-50 p-3 text-sm font-bold text-amber-800">Este post está oculto pela moderação.</div>@endif
    <x-post-card :post="(clone $post)->setRelation('comments', collect())" />

    @if ($pawers->isNotEmpty())
        <section class="card p-4">
            <h2 class="mb-2 text-sm font-extrabold text-stone-500">🐾 Patinhas de</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($pawers as $paw)
                    @if ($paw->user->activePet)
                        <a href="{{ route('pets.show', $paw->user->activePet) }}" class="chip bg-stone-50 !py-1 !pl-1"><x-pet-avatar :pet="$paw->user->activePet" size="xs" /> {{ $paw->user->activePet->name }}</a>
                    @else
                        <span class="chip bg-stone-50">{{ $paw->user->name }}</span>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section id="comentarios" class="card p-4">
        <h2 class="mb-3 font-display text-lg font-semibold">💬 Comentários ({{ $post->comments_count }})</h2>
        <div class="space-y-3">
            @foreach ($post->comments as $c)
                <div class="flex gap-3">
                    @if ($c->pet)<a href="{{ route('pets.show', $c->pet) }}"><x-pet-avatar :pet="$c->pet" size="sm" /></a>@else<x-user-avatar :user="$c->user" />@endif
                    <div class="flex-1 rounded-2xl bg-stone-50 px-3 py-2 text-sm">
                        <p><b>{{ $c->pet?->name ?? $c->user->name }}</b> <span class="text-xs text-stone-400">{{ $c->created_at->diffForHumans() }}</span></p>
                        <p class="text-stone-700">{{ $c->body }}</p>
                    </div>
                    @if (auth()->id() === $c->user_id || auth()->id() === $post->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('comments.destroy', $c) }}">@csrf @method('DELETE')<button class="text-stone-300 hover:text-rose-500" aria-label="Excluir">✕</button></form>
                    @endif
                </div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('comments.store', $post) }}" class="mt-4 flex gap-2">
            @csrf
            <input name="body" required maxlength="500" class="input" placeholder="Escreva um comentário…">
            <button class="btn-primary">Enviar</button>
        </form>
    </section>
</div>
</x-layouts.app>
