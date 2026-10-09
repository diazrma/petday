<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Pet;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HealthRecordController extends Controller
{
    public function store(Request $request, Pet $pet)
    {
        $this->authorizeOwner($pet);
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Catalog::HEALTH_KINDS))],
            'title' => ['required', 'string', 'max:80'],
            'applied_on' => ['nullable', 'date'],
            'next_due_on' => ['nullable', 'date', 'after_or_equal:applied_on'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $pet->healthRecords()->create($data);

        return redirect()->route('pets.show', [$pet, 'aba' => 'saude'])->with('success', 'Registro de saúde salvo! 💉');
    }

    public function destroy(HealthRecord $record)
    {
        $this->authorizeOwner($record->pet);
        $record->delete();

        return back()->with('success', 'Registro removido.');
    }
}
