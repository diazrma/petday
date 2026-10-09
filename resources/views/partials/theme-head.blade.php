{{-- Aplica o tema antes da pintura (evita "piscar" claro→escuro) e expõe toggleTheme() --}}
<script>
    (function () {
        var t = null;
        try { t = localStorage.getItem('petday-theme'); } catch (e) {}
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        window.toggleTheme = function () {
            var dark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('petday-theme', dark ? 'dark' : 'light'); } catch (e) {}
        };
    })();
</script>
