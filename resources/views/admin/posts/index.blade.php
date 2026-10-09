<x-layouts.admin title="Posts">
    <form class="mb-4 flex flex-wrap gap-2">
        <input name="q" value="{{ request('q') }}" class="input max-w-sm bg-surface" placeholder="Buscar no texto">
        @foreach (['' => 'Todos', 'denunciados' => '🚩 Denunciados', 'ocultos' => '🙈 Ocultos'] as $k => $l)
            <a href="{{ route('admin.posts.index', array_filter(['filtro' => $k, 'q' => request('q')])) }}" class="btn {{ request('filtro', '') === $k ? 'bg-night text-white' : 'bg-surface' }}">{{ $l }}</a>
        @endforeach
    </form>
    <div data-infinite-items class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ($posts as $post)
            <div class="card overflow-hidden {{ $post->hidden_at ? 'opacity-60' : '' }}">
                <a href="{{ route('posts.show', $post) }}" class="block aspect-video" style="background: {{ $post->pet->color }}">
                    @if ($post->imageUrl())<img src="{{ $post->imageUrl() }}" class="size-full object-cover" alt="">@else<span class="flex size-full items-center justify-center p-4 text-center text-sm font-bold text-white">{{ \Illuminate\Support\Str::limit($post->body, 90) }}</span>@endif
                </a>
                <div class="p-4 text-sm">
                    <p class="flex items-center gap-2"><x-pet-avatar :pet="$post->pet" size="xs" /><b>{{ $post->pet->name }}</b><span class="text-stone-400">· {{ $post->user->name }}</span></p>
                    <p class="mt-1 text-xs text-stone-500">{{ $post->created_at->format('d/m/Y H:i') }} · 🐾 {{ $post->paws_count }} · 💬 {{ $post->comments_count }}
                        @if ($post->reports_count)<span class="chip ml-1 bg-rose-100 text-rose-700">🚩 {{ $post->reports_count }}</span>@endif
                        @if ($post->hidden_at)<span class="chip ml-1 bg-amber-100 text-amber-700">Oculto</span>@endif</p>
                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="{{ route('admin.posts.hide', $post) }}" class="flex-1">@csrf @method('PATCH')<button class="btn-soft w-full !py-1.5 text-xs">{{ $post->hidden_at ? 'Mostrar' : 'Ocultar' }}</button></form>
                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" data-confirm-title="Excluir post?" data-confirm="O post e os comentários serão apagados." data-confirm-button="Excluir" data-confirm-icon="🗑️">@csrf @method('DELETE')<button class="btn-danger !py-1.5 text-xs">Excluir</button></form>
                    </div>
                </div>
            </div>
        @empty
            <p class="col-span-full py-10 text-center text-stone-500">Nenhum post.</p>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$posts" />
</x-layouts.admin>
