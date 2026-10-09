<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Notifications\PetDayNotification;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class VetPanelController extends Controller
{
    public function index(Request $request)
    {
        $vet = $request->user();
        $status = $request->query('status');
        $day = $request->query('dia') ? Carbon::parse($request->query('dia')) : null;

        $appointments = Appointment::with('pet.owner')
            ->where('vet_id', $vet->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($day, fn ($q) => $q->whereDate('scheduled_at', $day))
            ->when(! $status && ! $day, fn ($q) => $q->where('scheduled_at', '>=', now()->startOfDay()))
            ->orderBy('scheduled_at')
            ->simplePaginate(20)->withQueryString();

        $base = Appointment::where('vet_id', $vet->id);
        $stats = [
            'today' => (clone $base)->whereDate('scheduled_at', today())->whereIn('status', ['pending', 'confirmed'])->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'week' => (clone $base)->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->whereIn('status', ['confirmed', 'completed'])->count(),
            'patients' => (clone $base)->distinct('pet_id')->count('pet_id'),
        ];

        // Mini-agenda da semana
        $week = collect(range(0, 6))->map(function ($i) use ($vet) {
            $date = now()->startOfDay()->addDays($i);

            return [
                'date' => $date,
                'count' => Appointment::where('vet_id', $vet->id)->whereDate('scheduled_at', $date)->whereIn('status', ['pending', 'confirmed'])->count(),
            ];
        });

        return view('vet.index', compact('appointments', 'stats', 'week', 'status', 'day'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->vet_id === $request->user()->id, 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'completed', 'declined'])],
            'vet_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $appointment->update($data);

        $label = Catalog::APPOINTMENT_STATUS[$data['status']]['label'];
        $appointment->tutor->notify(new PetDayNotification(
            ['confirmed' => '✅', 'completed' => '🩺', 'declined' => '⚠️'][$data['status']],
            "Consulta de {$appointment->pet->name} ({$appointment->scheduled_at->format('d/m H:i')}): {$label}",
            route('appointments.index')
        ));

        return back()->with('success', "Consulta marcada como {$label}.");
    }
}
