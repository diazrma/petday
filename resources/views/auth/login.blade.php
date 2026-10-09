<x-layouts.guest title="Entrar">
    <h2 class="font-display text-4xl font-semibold">Oi de novo! 👋</h2>
    <p class="mt-1 text-stone-500">Entre para ver o que seus amigos de quatro patas aprontaram hoje.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
        @csrf
        <div>
            <label class="label" for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input">
            @error('email')<p class="mt-1 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">Senha</label>
            <input id="password" name="password" type="password" required class="input">
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold text-stone-600"><input type="checkbox" name="remember" class="rounded accent-brand-500"> Lembrar de mim</label>
        <button class="btn-primary w-full !py-3.5 text-base">Entrar</button>
    </form>
    <p class="mt-6 text-center text-sm text-stone-500">Novo por aqui? <a href="{{ route('register') }}" class="font-extrabold text-brand-600">Criar conta</a></p>
</x-layouts.guest>
