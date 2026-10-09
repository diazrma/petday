<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Paw;
use App\Models\Pet;
use App\Models\Post;
use App\Models\Report;
use App\Models\Story;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'usersNew' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'online' => User::where('last_seen_at', '>=', now()->subMinutes(15))->count(),
            'vets' => User::where('role', 'vet')->count(),
            'pets' => Pet::count(),
            'posts' => Post::count(),
            'paws' => Paw::count(),
            'stories' => Story::active()->count(),
            'appointments' => Appointment::whereIn('status', ['pending', 'confirmed'])->where('scheduled_at', '>=', now())->count(),
            'reports' => Report::where('status', 'open')->count(),
            'banned' => User::whereNotNull('banned_at')->count(),
        ];

        // Atividade dos últimos 14 dias (posts e cadastros)
        $activity = collect(range(13, 0))->map(function ($i) {
            $d = now()->subDays($i)->toDateString();

            return [
                'label' => now()->subDays($i)->format('d/m'),
                'posts' => Post::whereDate('created_at', $d)->count(),
                'users' => User::whereDate('created_at', $d)->count(),
            ];
        });

        $species = Pet::selectRaw('species, count(*) as total')->groupBy('species')->orderByDesc('total')->pluck('total', 'species');
        $topPets = Pet::withSum('posts', 'paws_count')->orderByDesc('posts_sum_paws_count')->limit(5)->get();
        $latestUsers = User::latest()->limit(6)->get();
        $openReports = Report::with('user', 'reportable')->where('status', 'open')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'activity', 'species', 'topPets', 'latestUsers', 'openReports'));
    }
}
