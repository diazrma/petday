<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} · PetDay Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php $openReports = \App\Models\Report::where('status', 'open')->count(); @endphp
<body class="min-h-screen bg-stone-100 font-sans text-ink antialiased" x-data="{ nav: false }">
<div class="flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-night p-5 text-stone-300 transition lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :class="nav && 'translate-x-0'">
        <a href="{{ route('admin.dashboard') }}" class="mb-8 flex items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-500 text-xl -rotate-6">🐾</span>
            <span><span class="block font-display text-2xl font-bold text-white">PetDay</span><span class="block text-[10px] font-extrabold uppercase tracking-widest text-brand-400">Painel admin</span></span>
        </a>
        @php
            $links = [
                ['admin.dashboard', '📊', 'Visão geral', 'admin.dashboard'],
                ['admin.users.index', '👥', 'Usuários', 'admin.users.*'],
                ['admin.pets.index', '🐶', 'Pets', 'admin.pets.*'],
                ['admin.posts.index', '🖼️', 'Posts', 'admin.posts.*'],
                ['admin.reports.index', '🚩', 'Denúncias', 'admin.reports.*'],
                ['admin.appointments.index', '🩺', 'Consultas', 'admin.appointments.*'],
            ];
        @endphp
        <nav class="space-y-1">
            @foreach ($links as [$route, $icon, $label, $pattern])
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-bold transition {{ request()->routeIs($pattern) ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' }}">
                    {{ $icon }} {{ $label }}
                    @if ($route === 'admin.reports.index' && $openReports)<span class="ml-auto rounded-full bg-rose-500 px-2 text-xs text-white">{{ $openReports }}</span>@endif
                </a>
            @endforeach
        </nav>
        <a href="{{ route('feed') }}" class="absolute inset-x-5 bottom-5 flex items-center gap-2 rounded-xl bg-white/5 px-3 py-2.5 text-sm font-bold hover:bg-white/10">← Voltar para a rede</a>
    </aside>
    <div x-cloak x-show="nav" @click="nav = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-stone-200 bg-stone-100/90 px-4 py-3 backdrop-blur sm:px-8">
            <button @click="nav = true" class="btn-ghost !p-2 lg:hidden" aria-label="Menu">☰</button>
            <h1 class="font-display text-2xl font-semibold">{{ $title ?? 'Admin' }}</h1>
            <x-theme-toggle class="btn-ghost ml-auto !p-2" />
            <span class="flex items-center gap-2 text-sm font-bold text-stone-500"><x-user-avatar :user="auth()->user()" size="size-8 text-xs" /> <span class="hidden sm:inline">{{ auth()->user()->name }}</span></span>
        </header>
        <main class="p-4 sm:p-8">
            @if (session('success'))<div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 ring-1 ring-rose-200">{{ $errors->first() }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
