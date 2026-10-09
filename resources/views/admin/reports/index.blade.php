<x-layouts.admin title="Denúncias">
    <div class="mb-4 flex gap-2">
        @foreach (['open' => 'Abertas', 'resolved' => 'Resolvidas', 'dismissed' => 'Descartadas', 'all' => 'Todas'] as $k => $l)
            <a href="{{ route('admin.reports.index', ['status' => $k]) }}" class="btn {{ $status === $k ? 'bg-night text-white' : 'bg-surface' }}">{{ $l }}</a>
        @endforeach
    </div>
    <div data-infinite-items class="space-y-3">
        @forelse ($reports as $r)
            <div class="card flex flex-wrap items-center gap-4 p-4">
                @if ($r->reportable)
                    <a href="{{ route('posts.show', $r->reportable) }}" class="size-16 shrink-0 overflow-hidden rounded-xl bg-stone-100">
                        @if ($r->reportable->imageUrl())<img src="{{ $r->reportable->imageUrl() }}" class="size-full object-cover" alt="">@else<span class="flex size-full items-center justify-center text-2xl">📝</span>@endif
                    </a>
                @endif
                <div class="min-w-0 flex-1 text-sm">
                    <p><b>{{ $r->user->name }}</b> denunciou {{ $r->reportable ? 'o post de '.$r->reportable->pet->name : 'um conteúdo removido' }}</p>
                    <p class="text-stone-600">“{{ $r->reason }}”</p>
                    <p class="text-xs text-stone-400">{{ $r->created_at->diffForHumans() }}</p>
                </div>
                @if ($r->status === 'open')
                    <form method="POST" action="{{ route('admin.reports.update', $r) }}" class="flex gap-2">@csrf @method('PATCH')
                        <button name="status" value="resolved" onclick="this.form.hide.value=1" class="btn-danger !py-1.5 text-xs">Ocultar post e resolver</button>
                        <button name="status" value="dismissed" class="btn-ghost !py-1.5 text-xs">Descartar</button>
                        <input type="hidden" name="hide" value="0">
                    </form>
                @else
                    <span class="chip {{ $r->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">{{ $r->status === 'resolved' ? 'Resolvida' : 'Descartada' }}</span>
                @endif
            </div>
        @empty
            <div class="card p-10 text-center text-stone-500">Nenhuma denúncia aqui 🎉</div>
        @endforelse
    </div>
    <x-infinite-scroll :paginator="$reports" />
</x-layouts.admin>
