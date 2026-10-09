<x-layouts.guest title="Criar conta">
    <h2 class="font-display text-4xl font-semibold">Bem-vindo ao bando 🐾</h2>
    <p class="mt-1 text-stone-500">Crie sua conta de tutor ou de clínica veterinária.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4" x-data="{ role: '{{ old('role', 'tutor') }}' }">
        @csrf
        <div class="grid grid-cols-2 gap-2 rounded-2xl bg-stone-100 p-1">
            <label class="cursor-pointer"><input type="radio" name="role" value="tutor" x-model="role" class="peer sr-only"><span class="block rounded-xl py-2.5 text-center font-extrabold text-stone-500 peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow">🏠 Sou tutor</span></label>
            <label class="cursor-pointer"><input type="radio" name="role" value="vet" x-model="role" class="peer sr-only"><span class="block rounded-xl py-2.5 text-center font-extrabold text-stone-500 peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow">🩺 Sou veterinário</span></label>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2 sm:col-span-1">
                <label class="label" for="name">Nome</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="input">
            </div>
            <div class="col-span-2 sm:col-span-1">
                <label class="label" for="username">Usuário</label>
                <input id="username" name="username" value="{{ old('username') }}" required class="input" placeholder="sem espaços">
            </div>
        </div>
        <div>
            <label class="label" for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input">
        </div>
        <div x-show="role === 'vet'" x-collapse class="space-y-3 rounded-2xl bg-sky-50 p-4 ring-1 ring-sky-100">
            <div>
                <label class="label" for="clinic_name">Nome da clínica</label>
                <input id="clinic_name" name="clinic_name" value="{{ old('clinic_name') }}" class="input bg-surface">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label" for="crmv">CRMV</label><input id="crmv" name="crmv" value="{{ old('crmv') }}" class="input bg-surface" placeholder="SP-12345"></div>
                <div><label class="label" for="specialty">Especialidade</label><input id="specialty" name="specialty" value="{{ old('specialty') }}" class="input bg-surface" placeholder="Clínico geral"></div>
            </div>
        </div>
        <div>
            <label class="label" for="city">Cidade</label>
            <input id="city" name="city" value="{{ old('city') }}" class="input">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="label" for="password">Senha</label><input id="password" name="password" type="password" required class="input"></div>
            <div><label class="label" for="password_confirmation">Confirmar</label><input id="password_confirmation" name="password_confirmation" type="password" required class="input"></div>
        </div>
        @if ($errors->any())
            <ul class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        @endif
        <button class="btn-primary w-full !py-3.5 text-base">Criar conta</button>
    </form>
    <p class="mt-6 text-center text-sm text-stone-500">Já tem conta? <a href="{{ route('login') }}" class="font-extrabold text-brand-600">Entrar</a></p>
</x-layouts.guest>
