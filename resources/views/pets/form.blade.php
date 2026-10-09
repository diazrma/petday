@php use App\Support\Catalog; $editing = $pet->exists; @endphp
<x-layouts.app :title="$editing ? 'Editar '.$pet->name : 'Novo pet'">
<div class="mx-auto max-w-2xl">
    <h1 class="mb-1 font-display text-4xl font-bold">{{ $editing ? 'Editar '.$pet->name : 'Cadastrar pet' }}</h1>
    <p class="mb-6 text-stone-500">{{ $editing ? 'Atualize o perfil.' : 'No PetDay, quem tem perfil é o pet. Você é o tutor!' }}</p>

    <form method="POST" action="{{ $editing ? route('pets.update', $pet) : route('pets.store') }}" enctype="multipart/form-data" class="card space-y-5 p-6" x-data="{ species: '{{ old('species', $pet->species ?? 'dog') }}', color: '{{ old('color', $pet->color) }}' }">
        @csrf @if ($editing) @method('PUT') @endif

        @if ($errors->any())
            <ul class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        @endif

        <div class="flex items-center gap-5" x-data="imagePreview('{{ $pet->avatarUrl() }}')">
            <label class="relative flex size-28 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-full text-5xl ring-4 ring-surface shadow-lg" :style="`background: ${color}22`">
                <template x-if="src"><img :src="src" class="absolute inset-0 size-full object-cover"></template>
                <span x-show="!src" x-text="{{ json_encode(collect(Catalog::SPECIES)->map(fn ($s) => $s['emoji'])) }}[species]"></span>
                <span class="absolute bottom-0 inset-x-0 bg-black/50 py-1 text-center text-[10px] font-bold text-white">FOTO</span>
                <input x-ref="file" type="file" name="avatar" accept="image/*" class="sr-only" @change="pick">
            </label>
            <div class="flex-1">
                <label class="label" for="name">Nome do pet</label>
                <input id="name" name="name" value="{{ old('name', $pet->name) }}" required maxlength="40" class="input text-lg font-bold" placeholder="Thor, Mel, Paçoca…">
            </div>
        </div>

        <div>
            <p class="label">Espécie</p>
            <div class="grid grid-cols-4 gap-2">
                @foreach (Catalog::SPECIES as $key => $s)
                    <label class="cursor-pointer"><input type="radio" name="species" value="{{ $key }}" x-model="species" class="peer sr-only">
                        <span class="flex flex-col items-center rounded-2xl bg-stone-50 py-3 text-sm font-bold ring-2 ring-transparent peer-checked:bg-brand-50 peer-checked:ring-brand-400"><span class="text-2xl">{{ $s['emoji'] }}</span>{{ $s['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div><label class="label" for="breed">Raça</label><input id="breed" name="breed" value="{{ old('breed', $pet->breed) }}" class="input" placeholder="SRD, Golden…"></div>
            <div><label class="label" for="gender">Sexo</label>
                <select id="gender" name="gender" class="input"><option value="">—</option><option value="male" @selected(old('gender', $pet->gender) === 'male')>Macho ♂</option><option value="female" @selected(old('gender', $pet->gender) === 'female')>Fêmea ♀</option></select>
            </div>
            <div><label class="label" for="birthdate">Nascimento</label><input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate', $pet->birthdate?->toDateString()) }}" max="{{ now()->toDateString() }}" class="input"></div>
            <div><label class="label" for="weight">Peso (kg)</label><input id="weight" type="number" step="0.01" min="0" name="weight" value="{{ old('weight', $pet->weight) }}" class="input"></div>
        </div>

        <div><label class="label" for="bio">Bio</label><textarea id="bio" name="bio" rows="3" maxlength="300" class="input resize-none" placeholder="Escreva como se fosse o pet falando 🐾">{{ old('bio', $pet->bio) }}</textarea></div>

        <div>
            <p class="label">Personalidade (até 5)</p>
            <div class="flex flex-wrap gap-2">
                @foreach (Catalog::PERSONALITY as $trait)
                    <label class="cursor-pointer"><input type="checkbox" name="personality[]" value="{{ $trait }}" @checked(in_array($trait, old('personality', $pet->personality ?? []))) class="peer sr-only">
                        <span class="chip bg-stone-100 text-stone-600 peer-checked:bg-brand-500 peer-checked:text-white">{{ $trait }}</span></label>
                @endforeach
            </div>
        </div>

        <div>
            <p class="label">Cor do perfil</p>
            <div class="flex gap-2">
                @foreach (Catalog::PET_COLORS as $c)
                    <label class="cursor-pointer"><input type="radio" name="color" value="{{ $c }}" x-model="color" class="peer sr-only"><span class="block size-9 rounded-full ring-offset-2 peer-checked:ring-2 peer-checked:ring-ink" style="background: {{ $c }}"></span></label>
                @endforeach
            </div>
        </div>

        <div><label class="label" for="cover">Foto de capa</label><input id="cover" type="file" name="cover" accept="image/*" class="input"></div>

        <div class="flex items-center gap-3 pt-2">
            <button class="btn-primary flex-1 !py-3.5 text-base">{{ $editing ? 'Salvar alterações' : 'Cadastrar pet 🐾' }}</button>
            @if ($editing)<a href="{{ route('pets.show', $pet) }}" class="btn-ghost">Cancelar</a>@endif
        </div>
    </form>

    @if ($editing)
        <form method="POST" action="{{ route('pets.destroy', $pet) }}" class="mt-4 text-center" data-confirm-title="Excluir {{ $pet->name }}?" data-confirm="Todo o diário, fotos e registros de saúde serão apagados. Isso não pode ser desfeito." data-confirm-button="Excluir pet" data-confirm-icon="⚠️">
            @csrf @method('DELETE')<button class="text-sm font-bold text-rose-500 hover:underline">Excluir perfil do pet</button>
        </form>
    @endif
</div>
</x-layouts.app>
