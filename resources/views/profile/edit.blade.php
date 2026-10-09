<x-layouts.app title="Conta">
<div class="mx-auto max-w-2xl">
    <h1 class="mb-5 font-display text-4xl font-bold">⚙️ Minha conta</h1>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card space-y-4 p-6">
        @csrf @method('PUT')
        @if ($errors->any())<ul class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>@endif
        <div class="flex items-center gap-4">
            <x-user-avatar :user="$user" size="size-20 text-2xl" />
            <div class="flex-1"><label class="label">Foto</label><input type="file" name="avatar" accept="image/*" class="input"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Nome</label><input name="name" value="{{ old('name', $user->name) }}" required class="input"></div>
            <div><label class="label">Usuário</label><input name="username" value="{{ old('username', $user->username) }}" required class="input"></div>
            <div><label class="label">E-mail</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input"></div>
            <div><label class="label">Cidade</label><input name="city" value="{{ old('city', $user->city) }}" class="input"></div>
        </div>
        <div><label class="label">Bio</label><textarea name="bio" rows="2" maxlength="300" class="input resize-none">{{ old('bio', $user->bio) }}</textarea></div>
        @if ($user->isVet())
            <div class="grid grid-cols-2 gap-4 rounded-2xl bg-sky-50 p-4">
                <div><label class="label">Clínica</label><input name="clinic_name" value="{{ old('clinic_name', $user->clinic_name) }}" class="input bg-surface"></div>
                <div><label class="label">CRMV</label><input name="crmv" value="{{ old('crmv', $user->crmv) }}" class="input bg-surface"></div>
                <div><label class="label">Especialidade</label><input name="specialty" value="{{ old('specialty', $user->specialty) }}" class="input bg-surface"></div>
                <div><label class="label">Endereço</label><input name="clinic_address" value="{{ old('clinic_address', $user->clinic_address) }}" class="input bg-surface"></div>
            </div>
        @endif
        <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Nova senha</label><input type="password" name="password" class="input" autocomplete="new-password"></div>
            <div><label class="label">Confirmar</label><input type="password" name="password_confirmation" class="input" autocomplete="new-password"></div>
        </div>
        <button class="btn-primary w-full !py-3.5">Salvar</button>
    </form>

    <div class="card mt-5 p-6">
        <h2 class="mb-3 font-display text-xl font-semibold">Meus pets</h2>
        @foreach ($user->pets as $p)
            <a href="{{ route('pets.show', $p) }}" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-stone-50"><x-pet-avatar :pet="$p" size="sm" /> <b>{{ $p->name }}</b> <span class="text-sm text-stone-500">{{ $p->speciesInfo()['label'] }}</span></a>
        @endforeach
        <a href="{{ route('pets.create') }}" class="btn-soft mt-3">＋ Adicionar pet</a>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">@csrf<button class="btn-ghost">Sair da conta</button></form>
</div>
</x-layouts.app>
