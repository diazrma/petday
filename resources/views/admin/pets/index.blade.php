@php use App\Support\Catalog; @endphp
<x-layouts.admin title="Pets">
    <form class="mb-4 flex flex-wrap gap-2">
        <input name="q" value="{{ $q }}" class="input max-w-sm bg-surface" placeholder="Buscar por nome ou raça">
        <select name="species" class="input w-auto bg-surface" onchange="this.form.submit()"><option value="">Todas as espécies</option>@foreach (Catalog::SPECIES as $k => $s)<option value="{{ $k }}" @selected(request('species') === $k)>{{ $s['emoji'] }} {{ $s['label'] }}</option>@endforeach</select>
        <button class="btn-primary">Filtrar</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="admin-table w-full min-w-[700px]">
            <thead class="border-b border-stone-100"><tr><th>Pet</th><th>Tutor</th><th>Espécie</th><th>Posts</th><th>Seguidores</th><th>Cadastro</th><th class="text-right">Ações</th></tr></thead>
            <tbody data-infinite-items class="divide-y divide-stone-100">
                @forelse ($pets as $p)
                    <tr class="hover:bg-stone-50">
                        <td><a href="{{ route('pets.show', $p) }}" class="flex items-center gap-3 font-bold"><x-pet-avatar :pet="$p" size="sm" /> {{ $p->name }}</a></td>
                        <td><a href="{{ route('admin.users.edit', $p->owner) }}" class="hover:underline">{{ $p->owner->name }}</a></td>
                        <td>{{ $p->emoji() }} {{ $p->breed ?: $p->speciesInfo()['label'] }}</td>
                        <td class="tabular-nums">{{ $p->posts_count }}</td>
                        <td class="tabular-nums">{{ $p->followers_count }}</td>
                        <td class="text-stone-500">{{ $p->created_at->format('d/m/Y') }}</td>
                        <td class="text-right"><form method="POST" action="{{ route('admin.pets.destroy', $p) }}" data-confirm-title="Excluir {{ $p->name }}?" data-confirm="O pet, o diário e as fotos serão apagados." data-confirm-button="Excluir pet" data-confirm-icon="⚠️">@csrf @method('DELETE')<button class="btn-ghost !px-3 !py-1.5 text-xs text-rose-600">Excluir</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-stone-500">Nenhum pet encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-infinite-scroll :paginator="$pets" />
</x-layouts.admin>
