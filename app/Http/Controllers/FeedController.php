<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\Post;
use App\Models\Story;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $myPetIds = $user->pets()->pluck('id');
        $followingIds = $user->following()->pluck('pets.id');
        $petIds = $myPetIds->merge($followingIds)->unique();

        $posts = Post::visible()
            ->with(['pet.owner', 'comments' => fn ($q) => $q->with('pet', 'user')->latest()->limit(2)])
            ->withPawedBy($user)
            ->when($petIds->isNotEmpty(), fn ($q) => $q->whereIn('pet_id', $petIds), fn ($q) => $q->orderByDesc('paws_count'))
            ->latest()
            ->simplePaginate(10);

        // Stories: agrupa por pet, com flag de "já visto"
        $stories = Story::active()
            ->whereIn('pet_id', $petIds)
            ->with('pet')
            ->withExists(['views as seen' => fn ($q) => $q->where('user_id', $user->id)])
            ->oldest()->get()
            ->groupBy('pet_id')
            ->map(fn ($group) => [
                'pet' => $group->first()->pet,
                'allSeen' => $group->every->seen,
                'latest' => $group->last(),
                'count' => $group->count(),
                // a pegada desbota conforme o rastro mais novo envelhece
                'life' => $group->last()->lifeLeft(),
            ])
            ->sortBy(fn ($s) => [$s['allSeen'], ! $myPetIds->contains($s['pet']->id)])
            ->values();

        // "Neste dia": memórias dos meus pets em anos/meses anteriores
        $memories = Post::visible()->with('pet')
            ->whereIn('pet_id', $myPetIds)
            ->whereMonth('diary_date', now()->month)
            ->whereDay('diary_date', now()->day)
            ->where('diary_date', '<', now()->toDateString())
            ->latest('diary_date')->limit(3)->get();

        $birthdays = Pet::whereIn('id', $petIds)->whereNotNull('birthdate')->get()->filter->isBirthdayToday();

        $upcoming = Appointment::with('pet', 'vet')->where('tutor_id', $user->id)->upcoming()->orderBy('scheduled_at')->limit(3)->get();
        $dueSoon = HealthRecord::with('pet')->whereIn('pet_id', $myPetIds)
            ->whereBetween('next_due_on', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->orderBy('next_due_on')->limit(3)->get();

        $suggestions = Pet::whereNotIn('id', $petIds)->withCount('followers')->orderByDesc('followers_count')->limit(5)->get();

        return view('feed.index', compact('posts', 'stories', 'memories', 'birthdays', 'upcoming', 'dueSoon', 'suggestions'));
    }
}
