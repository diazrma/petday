<x-layouts.admin :title="$user->name">
    <a href="{{ route('admin.users.index') }}" class="btn-ghost mb-4">← Usuários</a>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <section class="card p-6 text-center">
                <x-user-avatar :user="$user" size="size-24 text-3xl" class="mx-auto" />
                <h2 class="mt-3 font-display text-2xl font-semibold">{{ $user->name }}</h2>
                <p class="text-sm text-stone-500">{{ '@'.$user->username }} · {{ $user->roleLabel() }}</p>
                @if ($user->isBanned())<p class="chip mt-2 bg-rose-100 text-rose-700">Suspenso desde {{ $user->banned_at->format('d/m/Y H:i') }}</p>@endif
                <dl class="mt-5 grid grid-cols-2 gap-2 text-left text-sm">
                    <div class="rounded-xl bg-stone-50 p-3"><dt class="text-xs font-bold text-stone-400">Pets</dt><dd class="font-display text-xl font-bold">{{ $user->pets_count }}</dd></div>
                    <div class="rounded-xl bg-stone-50 p-3"><dt class="text-xs font-bold text-stone-400">Posts</dt><dd class="font-display text-xl font-bold">{{ $user->posts_count }}</dd></div>
                    <div class="rounded-xl bg-stone-50 p-3"><dt class="text-xs font-bold text-stone-400">Patinhas dadas</dt><dd class="font-display text-xl font-bold">{{ $user->paws_count }}</dd></div>
                    <div class="rounded-xl bg-stone-50 p-3"><dt class="text-xs font-bold text-stone-400">Consultas</dt><dd class="font-display text-xl font-bold">{{ $user->isVet() ? $user->vet_appointments_count : $user->tutor_appointments_count }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-stone-400">Cadastro em {{ $user->created_at->format('d/m/Y') }} · Visto {{ $user->last_seen_at?->diffForHumans() ?? 'nunca' }}</p>
            </section>
            @unless ($user->is(auth()->user()))
                <section class="card space-y-2 p-5">
                    <form method="POST" action="{{ route('admin.users.ban', $user) }}">@csrf @method('PATCH')
                        <button class="btn w-full {{ $user->isBanned() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $user->isBanned() ? '✅ Reativar conta' : '⛔ Suspender conta' }}</button></form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm-title="Excluir {{ $user->name }}?" data-confirm="Todos os pets e posts dessa conta serão apagados. Isso não pode ser desfeito." data-confirm-button="Excluir conta" data-confirm-icon="⚠️">@csrf @method('DELETE')
                        <button class="btn-danger w-full">🗑️ Excluir usuário</button></form>
                </section>
            @endunless
            @if ($user->pets->isNotEmpty())
                <section class="card p-5">
                    <h3 class="mb-2 font-display text-lg font-semibold">Pets</h3>
                    @foreach ($user->pets as $p)<a href="{{ route('pets.show', $p) }}" class="flex items-center gap-2 py-1.5 font-bold"><x-pet-avatar :pet="$p" size="xs" /> {{ $p->name }}</a>@endforeach
                </section>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card space-y-4 p-6 lg:col-span-2" x-data="{ role: '{{ old('role', $user->role) }}' }">
            @csrf @method('PUT')
            <h3 class="font-display text-xl font-semibold">Dados da conta</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Nome</label><input name="name" value="{{ old('name', $user->name) }}" required class="input"></div>
                <div><label class="label">Usuário</label><input name="username" value="{{ old('username', $user->username) }}" required class="input"></div>
                <div><label class="label">E-mail</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input"></div>
                <div><label class="label">Cidade</label><input name="city" value="{{ old('city', $user->city) }}" class="input"></div>
            </div>
            <div>
                <p class="label">Perfil de acesso</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['tutor' => '🏠 Tutor', 'vet' => '🩺 Veterinário', 'admin' => '🛡️ Admin'] as $k => $l)
                        <label class="cursor-pointer"><input type="radio" name="role" value="{{ $k }}" x-model="role" class="peer sr-only"><span class="block rounded-xl bg-stone-50 py-2.5 text-center text-sm font-extrabold ring-2 ring-transparent peer-checked:bg-brand-50 peer-checked:ring-brand-400">{{ $l }}</span></label>
                    @endforeach
                </div>
            </div>
            <div x-show="role === 'vet'" x-collapse class="grid gap-4 rounded-2xl bg-sky-50 p-4 sm:grid-cols-3">
                <div><label class="label">Clínica</label><input name="clinic_name" value="{{ old('clinic_name', $user->clinic_name) }}" class="input bg-surface"></div>
                <div><label class="label">CRMV</label><input name="crmv" value="{{ old('crmv', $user->crmv) }}" class="input bg-surface"></div>
                <div><label class="label">Especialidade</label><input name="specialty" value="{{ old('specialty', $user->specialty) }}" class="input bg-surface"></div>
            </div>
            <div><label class="label">Bio</label><textarea name="bio" rows="2" class="input resize-none">{{ old('bio', $user->bio) }}</textarea></div>
            <div><label class="label">Redefinir senha (opcional)</label><input type="password" name="password" class="input" autocomplete="new-password" placeholder="Deixe vazio para manter"></div>
            <button class="btn-primary">Salvar alterações</button>
        </form>
    </div>
</x-layouts.admin>
