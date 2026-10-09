<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::withCount(['pets', 'posts'])
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
            ->when($request->query('role'), fn ($query, $r) => $query->where('role', $r))
            ->when($request->query('status') === 'banned', fn ($query) => $query->whereNotNull('banned_at'))
            ->when($request->query('status') === 'active', fn ($query) => $query->whereNull('banned_at'))
            ->latest()->simplePaginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    public function edit(User $user)
    {
        $user->loadCount(['pets', 'posts', 'paws', 'tutorAppointments', 'vetAppointments']);
        $user->load('pets');

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(['tutor', 'vet', 'admin'])],
            'city' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:300'],
            'clinic_name' => ['nullable', 'string', 'max:120'],
            'crmv' => ['nullable', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:80'],
            'password' => ['nullable', Password::min(8)],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Você não pode remover seu próprio acesso de administrador.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'Usuário atualizado.');
    }

    public function toggleBan(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'Você não pode suspender a si mesmo.');
        $user->update(['banned_at' => $user->banned_at ? null : now()]);

        return back()->with('success', $user->banned_at ? "{$user->name} foi suspenso." : "{$user->name} foi reativado.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'Você não pode excluir a si mesmo.');
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuário excluído.');
    }
}
