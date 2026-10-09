<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Notifications\PetDayNotification;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::with('pet', 'vet')
            ->where('tutor_id', $request->user()->id)
            ->orderByRaw("CASE WHEN status IN ('pending','confirmed') AND scheduled_at >= ? THEN 0 ELSE 1 END", [now()])
            ->orderBy('scheduled_at')
            ->simplePaginate(15);

        return view('appointments.index', compact('appointments'));
    }

    public function create(Request $request, Pet $pet)
    {
        $this->authorizeOwner($pet);
        $vets = User::vets()->orderBy('clinic_name')->get();

        // Horários já ocupados por veterinário nos próximos 30 dias (para desabilitar no formulário)
        $busy = Appointment::whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('scheduled_at', [now(), now()->addDays(30)])
            ->get(['vet_id', 'scheduled_at'])
            ->groupBy('vet_id')
            ->map(fn ($g) => $g->map(fn ($a) => $a->scheduled_at->format('Y-m-d H:i'))->values());

        return view('appointments.create', compact('pet', 'vets', 'busy'));
    }

    public function store(Request $request, Pet $pet)
    {
        $this->authorizeOwner($pet);
        $data = $request->validate([
            'vet_id' => ['required', Rule::exists('users', 'id')->where('role', 'vet')->whereNull('banned_at')],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:'.now()->addDays(91)->toDateString()],
            'time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(array_keys(Catalog::APPOINTMENT_TYPES))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $scheduledAt = Carbon::parse($data['date'].' '.$data['time']);
        if ($scheduledAt->isPast()) {
            return back()->withErrors(['time' => 'Escolha um horário futuro.'])->withInput();
        }
        $taken = Appointment::where('vet_id', $data['vet_id'])->where('scheduled_at', $scheduledAt)
            ->whereIn('status', ['pending', 'confirmed'])->exists();
        if ($taken) {
            return back()->withErrors(['time' => 'Esse horário acabou de ser reservado. Escolha outro.'])->withInput();
        }

        $appointment = Appointment::create([
            'pet_id' => $pet->id,
            'tutor_id' => $request->user()->id,
            'vet_id' => $data['vet_id'],
            'scheduled_at' => $scheduledAt,
            'type' => $data['type'],
            'reason' => $data['reason'] ?? null,
        ]);

        $appointment->vet->notify(new PetDayNotification('📅', "Nova solicitação: {$pet->name} em ".$scheduledAt->format('d/m H:i'), route('vet.index')));

        return redirect()->route('pets.show', [$pet, 'mes' => $scheduledAt->format('Y-m')])
            ->with('success', 'Consulta solicitada! Você será avisado quando a clínica confirmar. 🩺');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->tutor_id === $request->user()->id, 403);
        abort_unless($appointment->isOpen(), 422);
        $appointment->update(['status' => 'cancelled']);
        $appointment->vet->notify(new PetDayNotification('❌', "{$appointment->pet->name} cancelou a consulta de ".$appointment->scheduled_at->format('d/m H:i'), route('vet.index')));

        return back()->with('success', 'Consulta cancelada.');
    }
}
