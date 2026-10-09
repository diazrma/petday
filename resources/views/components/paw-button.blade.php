@props(['post'])
<button type="button" data-paw-btn
        x-data="paw('{{ route('posts.paw', $post) }}', {{ $post->pawed ? 'true' : 'false' }}, {{ $post->paws_count }})"
        :data-pawed="pawed" @click="toggle($event)"
        :aria-pressed="pawed" aria-label="Dar patinha"
        class="group relative inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-extrabold transition"
        :class="pawed ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'bg-stone-100 text-stone-600 hover:bg-brand-50 hover:text-brand-700'">
    <svg viewBox="0 0 24 24" class="size-5" :class="pop && 'paw-pop'" @animationend="pop = false" fill="currentColor" aria-hidden="true">
        <ellipse cx="6" cy="9.5" rx="2.2" ry="2.8"/><ellipse cx="10" cy="5.5" rx="2.2" ry="2.9"/><ellipse cx="14.5" cy="5.5" rx="2.2" ry="2.9"/><ellipse cx="18.3" cy="9.7" rx="2.2" ry="2.8"/>
        <path d="M12.2 11.2c-2.9 0-6.4 3.7-6.4 6.5 0 1.8 1.4 2.8 3 2.8 1.3 0 2.2-.7 3.4-.7s2.1.7 3.4.7c1.6 0 3-1 3-2.8 0-2.8-3.5-6.5-6.4-6.5z"/>
    </svg>
    <span x-text="count">{{ $post->paws_count }}</span>
</button>
