<x-layouts.app title="Notificações">
<div class="mx-auto max-w-xl">
    <h1 class="mb-5 font-display text-4xl font-bold">🔔 Notificações</h1>
    <div data-infinite-items class="card divide-y divide-stone-100">
        @forelse ($notifications as $n)
            <a href="{{ $n->data['url'] ?? '#' }}" class="flex items-center gap-3 p-4 hover:bg-stone-50 {{ $n->read_at ? '' : 'bg-brand-50/60' }}">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-surface text-xl shadow-sm">{{ $n->data['icon'] ?? '🐾' }}</span>
                <span class="flex-1 text-sm font-semibold">{{ $n->data['text'] ?? '' }}</span>
                <span class="text-xs text-stone-400">{{ $n->created_at->diffForHumans(null, true) }}</span>
            </a>
        @empty
            <p class="p-10 text-center text-stone-500">Nada por aqui ainda.</p>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$notifications" />
</div>
</x-layouts.app>
