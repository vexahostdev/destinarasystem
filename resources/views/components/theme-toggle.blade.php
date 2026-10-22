<button type="button"
        x-data="{ dark: document.documentElement.classList.contains('dark') }"
        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('destinara-theme', dark ? 'dark' : 'light') } catch (e) {}"
        :title="dark ? 'Mode terang' : 'Mode gelap'"
        :aria-label="dark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
        {{ $attributes->merge(['class' => 'p-2 rounded-full text-slate-600 hover:text-[#1F2B45] hover:bg-slate-100 transition-colors cursor-pointer']) }}>
    <span class="material-symbols-outlined text-[20px] block" x-text="dark ? 'light_mode' : 'dark_mode'">dark_mode</span>
</button>
