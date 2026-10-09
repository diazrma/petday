<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');
        $reports = Report::with(['user', 'reportable'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()->simplePaginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'dismissed'])],
            'hide' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('hide') && $report->reportable) {
            $report->reportable->update(['hidden_at' => now()]);
        }
        $report->update(['status' => $data['status']]);

        return back()->with('success', 'Denúncia atualizada.');
    }
}
