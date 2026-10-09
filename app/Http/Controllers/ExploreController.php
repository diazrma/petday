<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Post;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'species' => ['nullable', Rule::in(array_keys(Catalog::SPECIES))],
            'mood' => ['nullable', Rule::in(array_keys(Catalog::MOODS))],
        ]);
        $q = trim((string) $request->query('q'));

        $pets = Pet::query()
            ->withCount('followers')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('breed', 'like', "%{$q}%")))
            ->when($request->species, fn ($query, $s) => $query->where('species', $s))
            ->orderByDesc('followers_count')
            ->limit(12)->get();

        $posts = Post::visible()->with('pet')
            ->when($request->species, fn ($query, $s) => $query->whereHas('pet', fn ($p) => $p->where('species', $s)))
            ->when($request->mood, fn ($query, $m) => $query->where('mood', $m))
            ->when($q, fn ($query) => $query->where('body', 'like', "%{$q}%"))
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('paws_count')->latest()
            ->simplePaginate(18)->withQueryString();

        return view('explore.index', compact('pets', 'posts', 'q'));
    }
}
