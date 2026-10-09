@php use App\Support\Catalog; @endphp
<x-layouts.app title="Explorar">
<div class="mx-auto max-w-5xl">
    <form class="mb-5 space-y-3">
        <input name="q" value="{{ $q }}" class="input !py-4 text-base" placeholder="🔎 Buscar pets, raças ou momentos…">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('explore', array_filter(['q' => $q, 'mood' => request('mood')])) }}" class="chip shrink-0 !px-3 !py-1.5 {{ request('species') ? 'bg-surface' : 'bg-night text-white' }}">Todos</a>
            @foreach (Catalog::SPECIES as $k => $s)
                <a href="{{ route('explore', array_filter(['q' => $q, 'species' => $k, 'mood' => request('mood')])) }}" class="chip shrink-0 !px-3 !py-1.5 {{ request('species') === $k ? 'bg-night text-white' : 'bg-surface' }}">{{ $s['emoji'] }} {{ $s['label'] }}</a>
            @endforeach
        </div>
        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach (Catalog::MOODS as $k => $m)
                <a href="{{ route('explore', array_filter(['q' => $q, 'species' => request('species'), 'mood' => request('mood') === $k ? null : $k])) }}" class="chip shrink-0 !px-3 !py-1.5 {{ request('mood') === $k ? 'text-white' : 'bg-surface' }}" @if (request('mood') === $k) style="background: {{ $m['color'] }}" @endif>{{ $m['emoji'] }} {{ $m['label'] }}</a>
            @endforeach
        </div>
    </form>

    @if ($pets->isNotEmpty())
        <h2 class="mb-3 font-display text-xl font-semibold">Pets em destaque</h2>
        <div class="-mx-4 mb-6 flex gap-3 overflow-x-auto px-4 pb-2 lg:mx-0 lg:px-0">
            @foreach ($pets as $pet)
                <a href="{{ route('pets.show', $pet) }}" class="card flex w-36 shrink-0 flex-col items-center p-4 text-center">
                    <x-pet-avatar :pet="$pet" size="lg" />
                    <p class="mt-2 w-full truncate font-extrabold">{{ $pet->name }}</p>
                    <p class="w-full truncate text-xs text-stone-500">{{ $pet->breed ?: $pet->speciesInfo()['label'] }}</p>
                    <p class="mt-1 text-xs font-bold text-brand-600">{{ $pet->followers_count }} seguidores</p>
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="mb-3 font-display text-xl font-semibold">🔥 Mais patinhas do mês</h2>
    <div data-infinite-items class="grid grid-cols-3 gap-1 sm:gap-3">
        @forelse ($posts as $post)
            <a href="{{ route('posts.show', $post) }}" class="group relative aspect-square overflow-hidden rounded-xl sm:rounded-2xl" style="background: {{ $post->moodInfo()['color'] ?? $post->pet->color }}">
                @if ($post->imageUrl())<img src="{{ $post->imageUrl() }}" loading="lazy" class="size-full object-cover transition group-hover:scale-105" alt="">
                @else<span class="flex size-full items-center justify-center p-3 text-center text-sm font-bold text-white">{{ \Illuminate\Support\Str::limit($post->body, 80) }}</span>@endif
                <span class="absolute bottom-2 left-2 flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-xs font-bold text-white">🐾 {{ $post->paws_count }}</span>
                <span class="absolute right-2 top-2 rounded-full bg-surface/90 px-2 py-0.5 text-xs font-bold">{{ $post->pet->name }}</span>
            </a>
        @empty
            <p class="col-span-3 py-10 text-center text-stone-500">Nada encontrado.</p>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$posts" />
</div>
</x-layouts.app>
