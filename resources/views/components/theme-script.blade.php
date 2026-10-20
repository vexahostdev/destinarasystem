{{-- Pasang tema sebelum render (anti-kedip). Kunci sama dengan Destinara Compro. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('destinara-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', stored === 'dark' || (!stored && prefersDark));
        } catch (e) {}
    })();
</script>
