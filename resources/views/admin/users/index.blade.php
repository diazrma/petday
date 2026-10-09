<x-layouts.admin title="Usuários">
    <form class="mb-4 flex flex-wrap gap-2">
        <input name="q" value="{{ $q }}" class="input max-w-sm bg-surface" placeholder="Buscar por nome, e-mail ou usuário">
        <select name="role" class="input w-auto bg-surface" onchange="this.form.submit()">
            <option value="">Todos os perfis</option>
            @foreach (['tutor' => 'Tutores', 'vet' => 'Veterinários', 'admin' => 'Administradores'] as $k => $l)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $l }}</option>@endforeach
        </select>
        <select name="status" class="input w-auto bg-surface" onchange="this.form.submit()">
            <option value="">Qualquer status</option><option value="active" @selected(request('status') === 'active')>Ativos</option><option value="banned" @selected(request('status') === 'banned')>Suspensos</option>
        </select>
        <button class="btn-primary">Filtrar</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="admin-table w-full min-w-[760px]">
            <thead class="border-b border-stone-100"><tr><th>Usuário</th><th>Perfil</th><th>Pets</th><th>Posts</th><th>Cadastro</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody data-infinite-items class="divide-y divide-stone-100">
                @forelse ($users as $u)
                    <tr class="hover:bg-stone-50">
                        <td><a href="{{ route('admin.users.edit', $u) }}" class="flex items-center gap-3"><x-user-avatar :user="$u" /><span><b class="block">{{ $u->name }}</b><span class="text-xs text-stone-500">{{ '@'.$u->username }} · {{ $u->email }}</span></span></a></td>
                        <td><span class="chip {{ ['admin' => 'bg-violet-100 text-violet-700', 'vet' => 'bg-sky-100 text-sky-700', 'tutor' => 'bg-stone-100 text-stone-600'][$u->role] }}">{{ $u->roleLabel() }}</span></td>
                        <td class="tabular-nums">{{ $u->pets_count }}</td>
                        <td class="tabular-nums">{{ $u->posts_count }}</td>
                        <td class="text-stone-500">{{ $u->created_at->format('d/m/Y') }}</td>
                        <td>@if ($u->isBanned())<span class="chip bg-rose-100 text-rose-700">Suspenso</span>@else<span class="chip bg-emerald-100 text-emerald-700">Ativo</span>@endif</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Editar</a>
                                @unless ($u->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.ban', $u) }}">@csrf @method('PATCH')<button class="btn-ghost !px-3 !py-1.5 text-xs {{ $u->isBanned() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $u->isBanned() ? 'Reativar' : 'Suspender' }}</button></form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-stone-500">Nenhum usuário encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-infinite-scroll :paginator="$users" />
</x-layouts.admin>
