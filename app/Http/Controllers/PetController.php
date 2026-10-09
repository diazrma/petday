<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Notifications\PetDayNotification;
use App\Services\PetCalendar;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PetController extends Controller
{
    public function create()
    {
        return view('pets.form', ['pet' => new Pet(['color' => Catalog::PET_COLORS[0]])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['avatar'] = $this->storeImage($request->file('avatar'), 'pets');
        $data['cover'] = $this->storeImage($request->file('cover'), 'covers');

        $pet = $request->user()->pets()->create($data);
        if (! $request->user()->active_pet_id) {
            $request->user()->update(['active_pet_id' => $pet->id]);
        }

        return redirect()->route('pets.show', $pet)->with('success', "{$pet->name} agora faz parte do PetDay! 🎉");
    }

    public function show(Request $request, Pet $pet)
    {
        $pet->load('owner')->loadCount(['followers', 'posts' => fn ($q) => $q->visible()]);
        $tab = in_array($request->query('aba'), ['diario', 'posts', 'saude']) ? $request->query('aba') : 'diario';
        $isOwner = $request->user()->id === $pet->user_id;

        $calendar = PetCalendar::for($pet, $request->query('mes'));
        $posts = $tab === 'posts'
            ? $pet->posts()->visible()->withPawedBy($request->user())->latest()->simplePaginate(12)->withQueryString()
            : null;

        $health = $pet->healthRecords()->orderByDesc('applied_on')->get();
        $appointments = $pet->appointments()->with('vet')->orderByDesc('scheduled_at')->limit(10)->get();
        $hasStories = $pet->stories()->active()->exists();
        $totalPaws = $pet->posts()->visible()->sum('paws_count');

        return view('pets.show', [
            'pet' => $pet,
            'tab' => $tab,
            'isOwner' => $isOwner,
            'calendar' => $calendar,
            'weeks' => $tab === 'diario' ? $calendar->weeks() : [],
            'summary' => $calendar->summary(),
            'posts' => $posts,
            'health' => $health,
            'appointments' => $appointments,
            'hasStories' => $hasStories,
            'totalPaws' => $totalPaws,
            'streak' => $pet->streak(),
            'following' => $pet->isFollowedBy($request->user()),
        ]);
    }

    public function day(Request $request, Pet $pet, string $date)
    {
        $day = Carbon::parse($date);
        $posts = $pet->posts()->visible()->withPawedBy($request->user())->with('pet')
            ->whereDate('diary_date', $day)->oldest()->get();
        $appointments = $pet->appointments()->with('vet')->whereDate('scheduled_at', $day)->get();
        $health = $pet->healthRecords()->where(fn ($q) => $q->whereDate('next_due_on', $day)->orWhereDate('applied_on', $day))->get();

        return view('pets.day', compact('pet', 'day', 'posts', 'appointments', 'health'));
    }

    public function edit(Pet $pet)
    {
        $this->authorizeOwner($pet);

        return view('pets.form', compact('pet'));
    }

    public function update(Request $request, Pet $pet)
    {
        $this->authorizeOwner($pet);
        $data = $this->validated($request);
        $data['avatar'] = $this->storeImage($request->file('avatar'), 'pets', $pet->avatar);
        $data['cover'] = $this->storeImage($request->file('cover'), 'covers', $pet->cover);
        $pet->update($data);

        return redirect()->route('pets.show', $pet)->with('success', 'Perfil atualizado!');
    }

    public function destroy(Request $request, Pet $pet)
    {
        $this->authorizeOwner($pet);
        $pet->delete();
        if ($request->user()->active_pet_id === $pet->id) {
            $request->user()->update(['active_pet_id' => $request->user()->pets()->value('id')]);
        }

        return redirect()->route('feed')->with('success', 'Perfil do pet removido.');
    }

    public function activate(Request $request, Pet $pet)
    {
        abort_unless($pet->user_id === $request->user()->id, 403);
        $request->user()->update(['active_pet_id' => $pet->id]);

        return back()->with('success', "Agora você está usando o PetDay como {$pet->name} {$pet->emoji()}");
    }

    public function follow(Request $request, Pet $pet)
    {
        $user = $request->user();
        $result = $user->following()->toggle($pet->id);

        if ($result['attached'] && $pet->user_id !== $user->id) {
            $pet->owner->notify(new PetDayNotification('🐾', "{$user->name} começou a seguir {$pet->name}", route('pets.show', $pet)));
        }

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'species' => ['required', Rule::in(array_keys(Catalog::SPECIES))],
            'breed' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'bio' => ['nullable', 'string', 'max:300'],
            'personality' => ['nullable', 'array', 'max:5'],
            'personality.*' => [Rule::in(Catalog::PERSONALITY)],
            'color' => ['required', Rule::in(Catalog::PET_COLORS)],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'cover' => ['nullable', 'image', 'max:8192'],
        ]);
        $data['personality'] = $data['personality'] ?? [];
        unset($data['avatar'], $data['cover']);

        return $data;
    }
}
