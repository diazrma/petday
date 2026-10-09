@php use App\Support\Catalog; @endphp
<x-layouts.admin title="Consultas">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.appointments.index') }}" class="btn {{ ! request('status') ? 'bg-night text-white' : 'bg-surface' }}">Todas</a>
        @foreach (Catalog::APPOINTMENT_STATUS as $k => $s)<a href="{{ route('admin.appointments.index', ['status' => $k]) }}" class="btn {{ request('status') === $k ? 'bg-night text-white' : 'bg-surface' }}">{{ $s['label'] }}</a>@endforeach
    </div>
    <div class="card overflow-x-auto">
        <table class="admin-table w-full min-w-[760px]">
            <thead class="border-b border-stone-100"><tr><th>Data</th><th>Pet</th><th>Tutor</th><th>Clínica</th><th>Tipo</th><th>Status</th></tr></thead>
            <tbody data-infinite-items class="divide-y divide-stone-100">
                @forelse ($appointments as $a)
                    <tr class="hover:bg-stone-50">
                        <td class="font-bold tabular-nums">{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('pets.show', $a->pet) }}" class="flex items-center gap-2 font-bold"><x-pet-avatar :pet="$a->pet" size="xs" /> {{ $a->pet->name }}</a></td>
                        <td>{{ $a->tutor->name }}</td>
                        <td>{{ $a->vet->clinic_name }}<br><span class="text-xs text-stone-500">Dr(a). {{ $a->vet->name }}</span></td>
                        <td>{{ $a->typeLabel() }}</td>
                        <td><span class="chip {{ $a->statusInfo()['class'] }}">{{ $a->statusInfo()['label'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-stone-500">Nenhuma consulta.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-infinite-scroll :paginator="$appointments" />
</x-layouts.admin>
