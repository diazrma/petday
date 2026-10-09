@props(['paginator'])
{{-- Rolagem infinita: ao chegar perto do fim, busca a próxima página e anexa os itens em [data-infinite-items]. --}}
@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasMorePages())
    <div data-infinite-next="{{ $paginator->nextPageUrl() }}" x-data="infiniteScroll(@js($paginator->nextPageUrl()))" class="flex justify-center py-6">
        <span x-show="loading" class="flex items-center gap-2 text-sm font-bold text-stone-400"><span class="animate-bounce">🐾</span> Farejando mais…</span>
        {{-- Sem JS, ou se der erro, continua dando para avançar --}}
        <a x-show="!loading" href="{{ $paginator->nextPageUrl() }}" @click.prevent="load()" class="btn-soft" x-text="failed ? 'Tentar de novo' : 'Carregar mais'">Carregar mais</a>
    </div>
@elseif ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->currentPage() > 1)
    <p class="py-6 text-center text-sm font-bold text-stone-400">🐾 Você chegou ao fim</p>
@endif
