<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\Request;

class PetController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $pets = Pet::with('owner')->withCount(['posts', 'followers'])
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('breed', 'like', "%{$q}%"))
            ->when($request->query('species'), fn ($query, $s) => $query->where('species', $s))
            ->latest()->simplePaginate(20)->withQueryString();

        return view('admin.pets.index', compact('pets', 'q'));
    }

    public function destroy(Pet $pet)
    {
        $pet->delete();

        return back()->with('success', 'Pet removido.');
    }
}
