<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}PetDay</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $me = auth()->user();
    $myPets = $me->pets()->orderBy('name')->get();
    $current = $me->currentPet();
    $unread = $me->unreadNotifications()->count();
@endphp
<body class="min-h-screen bg-cream font-sans text-ink antialiased" x-data="{ composer: false, composerTab: 'post' }" @open-composer.window="composer = true; composerTab = $event.detail?.tab ?? 'post'">

<div class="mx-auto flex max-w-7xl gap-6 px-0 lg:px-6">
    {{-- Sidebar desktop --}}
    <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col py-6 lg:flex">
        <a href="{{ route('feed') }}" class="mb-8 flex items-center gap-2 px-4">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-500 text-2xl shadow-lg shadow-brand-500/40 -rotate-6">🐾</span>
            <span class="font-display text-3xl font-bold tracking-tight">Pet<span class="text-brand-500">Day</span></span>
        </a>

        <nav class="space-y-1">
            <a href="{{ route('feed') }}" class="nav-link {{ request()->routeIs('feed') ? 'active' : '' }}">🏠 Início</a>
            <a href="{{ route('explore') }}" class="nav-link {{ request()->routeIs('explore') ? 'active' : '' }}">🧭 Explorar</a>
            @if ($current)
                <a href="{{ route('pets.show', $current) }}" class="nav-link {{ request()->routeIs('pets.show') && request()->route('pet')?->is($current) ? 'active' : '' }}">📅 Diário de {{ $current->name }}</a>
            @endif
            <a href="{{ route('appointments.index') }}" class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}">🩺 Consultas</a>
            <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                🔔 Notificações
                @if ($unread)<span class="ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-xs text-white">{{ $unread }}</span>@endif
            </a>
            @if ($me->isVet())
                <a href="{{ route('vet.index') }}" class="nav-link {{ request()->routeIs('vet.*') ? 'active' : '' }}">🏥 Minha clínica</a>
            @endif
            @if ($me->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-link">🛡️ Painel admin</a>
            @endif
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">⚙️ Conta</a>
            <x-theme-toggle label class="nav-link w-full" />
        </nav>

        <button @click="$dispatch('open-composer')" class="btn-primary mt-6 w-full !py-3.5 text-base">＋ Registrar o dia</button>

        <div class="mt-auto" x-data="{ open: false }">
            <div x-cloak x-show="open" x-transition @click.outside="open = false" class="card mb-2 p-2">
                <p class="px-3 py-2 text-xs font-extrabold uppercase text-stone-400">Usar como</p>
                @foreach ($myPets as $p)
                    <form method="POST" action="{{ route('pets.activate', $p) }}">
                        @csrf
                        <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-bold hover:bg-stone-50">
                            <x-pet-avatar :pet="$p" size="xs" /> {{ $p->name }}
                            @if ($current?->is($p))<span class="ml-auto text-brand-500">●</span>@endif
                        </button>
                    </form>
                @endforeach
                <a href="{{ route('pets.create') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-brand-600 hover:bg-brand-50">＋ Adicionar pet</a>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-100 mt-1 pt-1">
                    @csrf <button class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-stone-500 hover:bg-stone-50">Sair</button>
                </form>
            </div>
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-2xl p-2 text-left hover:bg-surface">
                @if ($current)
                    <x-pet-avatar :pet="$current" size="md" />
                @else
                    <x-user-avatar :user="$me" size="size-11 text-sm" />
                @endif
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-extrabold">{{ $current?->name ?? $me->name }}</span>
                    <span class="block truncate text-xs text-stone-500">{{ '@'.$me->username }} · {{ $me->roleLabel() }}</span>
                </span>
                <span class="text-stone-400">⌃</span>
            </button>
        </div>
    </aside>

    {{-- Conteúdo --}}
    <div class="min-w-0 flex-1 pb-28 lg:pb-10">
        {{-- Topbar mobile --}}
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-orange-900/5 bg-cream/90 px-4 py-3 backdrop-blur lg:hidden">
            <a href="{{ route('feed') }}" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-xl bg-brand-500 text-lg -rotate-6">🐾</span>
                <span class="font-display text-2xl font-bold">Pet<span class="text-brand-500">Day</span></span>
            </a>
            <div class="flex items-center gap-1">
                @if ($me->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn-ghost !p-2" aria-label="Painel admin">🛡️</a>@endif
                @if ($me->isVet())<a href="{{ route('vet.index') }}" class="btn-ghost !p-2" aria-label="Minha clínica">🏥</a>@endif
                <a href="{{ route('notifications.index') }}" class="btn-ghost relative !p-2" aria-label="Notificações">🔔
                    @if ($unread)<span class="absolute right-1 top-1 size-2 rounded-full bg-rose-500"></span>@endif
                </a>
                <x-theme-toggle class="btn-ghost !p-2" />
                <a href="{{ route('profile.edit') }}" class="btn-ghost !p-2" aria-label="Conta">⚙️</a>
            </div>
        </header>

        <main class="px-4 pt-4 lg:px-0 lg:pt-6">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 ring-1 ring-rose-200">{{ session('error') }}</div>
            @endif
            @if ($errors->any() && ! request()->routeIs('pets.create', 'pets.edit', 'appointments.create', 'profile.edit'))
                <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 ring-1 ring-rose-200">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

{{-- Bottom nav mobile --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-orange-900/5 bg-surface/95 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
    <div class="mx-auto flex max-w-md items-center justify-around py-2 text-2xl">
        <a href="{{ route('feed') }}" class="rounded-2xl p-2 {{ request()->routeIs('feed') ? 'bg-brand-50' : '' }}" aria-label="Início">🏠</a>
        <a href="{{ route('explore') }}" class="rounded-2xl p-2 {{ request()->routeIs('explore') ? 'bg-brand-50' : '' }}" aria-label="Explorar">🧭</a>
        <button @click="$dispatch('open-composer')" class="-mt-8 flex size-14 items-center justify-center rounded-2xl bg-brand-500 text-3xl text-white shadow-xl shadow-brand-500/40 -rotate-6" aria-label="Registrar o dia">＋</button>
        <a href="{{ route('appointments.index') }}" class="rounded-2xl p-2 {{ request()->routeIs('appointments.*') ? 'bg-brand-50' : '' }}" aria-label="Consultas">🩺</a>
        @if ($current)
            <a href="{{ route('pets.show', $current) }}" class="rounded-2xl p-1" aria-label="Diário do pet"><x-pet-avatar :pet="$current" size="sm" /></a>
        @else
            <a href="{{ route('pets.create') }}" class="rounded-2xl p-2" aria-label="Adicionar pet">🐾</a>
        @endif
    </div>
</nav>

@include('partials.composer', ['current' => $current])
</body>
</html>
