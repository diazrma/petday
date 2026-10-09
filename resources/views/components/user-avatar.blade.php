@props(['user', 'size' => 'size-9 text-xs'])
<span {{ $attributes->merge(['class' => "$size inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-stone-200 font-extrabold text-stone-600"]) }}>
    @if ($user->avatarUrl())
        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-full object-cover">
    @else
        {{ $user->initials() }}
    @endif
</span>
