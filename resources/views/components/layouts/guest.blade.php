<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PetDay' }} · PetDay</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink antialiased">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Painel de boas-vindas: laranja no modo claro, noturno com brilho laranja no escuro --}}
    <section class="hero-panel relative hidden overflow-hidden p-12 text-white lg:flex lg:flex-col">
        <div class="hero-glow-a pointer-events-none absolute -top-24 -right-24 size-96 rounded-full"></div>
        <div class="hero-glow-b pointer-events-none absolute -bottom-32 -left-16 size-[28rem] rounded-full"></div>
        <a href="/" class="relative flex items-center gap-3">
            <span class="hero-logo flex size-12 items-center justify-center rounded-2xl text-2xl -rotate-6">🐾</span>
            <span class="font-display text-4xl font-bold">Pet<span class="hero-accent">Day</span></span>
        </a>
        <div class="relative my-auto max-w-md">
            <h1 class="font-display text-5xl font-bold leading-tight">O diário social <span class="hero-accent">do dia a dia</span> do seu pet<span class="hero-accent">.</span></h1>
            <p class="hero-muted mt-4 text-lg">Cada momento vira <b class="hero-accent">um dia no calendário</b>. Cada carinho vira <b class="hero-accent">uma patinha</b>. E a saúde fica em dia com <b class="hero-accent">agendamento direto com o veterinário</b>.</p>
            <div class="mt-10 grid grid-cols-7 gap-2">
                {{-- mini calendário: dias com momento e dias com patinha --}}
                @foreach (['🐶','','🎾','😴','','🦴','🛁','','🐱','🥰','','🩺','🌳','🎂'] as $i => $e)
                    <div class="{{ $e ? 'hero-tile' : 'hero-tile-empty' }} flex aspect-square items-center justify-center rounded-xl text-xl">
                        @if ($e){{ $e }}@else<svg viewBox="0 0 24 24" class="hero-paw size-5"><path d="M3.8,9.5a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M7.8,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M12.3,5.5a2.2,2.9 0 1,0 4.4,0a2.2,2.9 0 1,0 -4.4,0z M16.1,9.7a2.2,2.8 0 1,0 4.4,0a2.2,2.8 0 1,0 -4.4,0z M12.2,11.2c-2.9,0 -6.4,3.7 -6.4,6.5c0,1.8 1.4,2.8 3,2.8c1.3,0 2.2,-0.7 3.4,-0.7s2.1,0.7 3.4,0.7c1.6,0 3,-1 3,-2.8c0,-2.8 -3.5,-6.5 -6.4,-6.5z"/></svg>@endif
                    </div>
                @endforeach
            </div>
        </div>
        <p class="relative text-sm font-bold">Patinhas no lugar de likes <span class="hero-accent">·</span> 📅 Diário em calendário <span class="hero-accent">·</span> 🩺 Agenda veterinária</p>
    </section>
    <main class="relative flex items-center justify-center p-6">
        <x-theme-toggle label class="btn-ghost absolute right-4 top-4 text-sm" />
        <div class="w-full max-w-md">
            <a href="/" class="mb-8 flex items-center gap-2 lg:hidden">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-500 text-xl -rotate-6">🐾</span>
                <span class="font-display text-3xl font-bold">Pet<span class="text-brand-500">Day</span></span>
            </a>
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
