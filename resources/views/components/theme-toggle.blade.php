@props(['label' => false])
<button type="button" onclick="toggleTheme()" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }} aria-label="Alternar modo escuro" title="Alternar modo escuro">
    <span class="dark:hidden">🌙</span><span class="hidden dark:inline">☀️</span>
    @if ($label)<span class="dark:hidden">Modo escuro</span><span class="hidden dark:inline">Modo claro</span>@endif
</button>
