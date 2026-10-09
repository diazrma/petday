@props(['pet', 'size' => 'md', 'ring' => false, 'seen' => false])
@php
    $sizes = ['xs' => 'size-7 text-sm', 'sm' => 'size-9 text-base', 'md' => 'size-11 text-xl', 'lg' => 'size-16 text-3xl', 'xl' => 'size-28 text-6xl sm:size-36 sm:text-7xl'];
    $cls = $sizes[$size] ?? $sizes['md'];
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 rounded-full '.($ring ? ($seen ? 'bg-stone-300 p-[2px]' : 'story-ring p-[3px]') : '')]) }}>
    <span class="{{ $cls }} flex items-center justify-center overflow-hidden rounded-full ring-2 ring-surface" style="background: {{ $pet->color }}22">
        @if ($pet->avatarUrl())
            <img src="{{ $pet->avatarUrl() }}" alt="{{ $pet->name }}" class="size-full object-cover">
        @else
            <span aria-hidden="true">{{ $pet->emoji() }}</span>
        @endif
    </span>
</span>
