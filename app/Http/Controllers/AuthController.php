<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember') || self::fromApp($request))) {
            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('feed'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['tutor', 'vet'])],
            'clinic_name' => ['nullable', 'required_if:role,vet', 'string', 'max:120'],
            'crmv' => ['nullable', 'required_if:role,vet', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
        ], [
            'clinic_name.required_if' => 'Informe o nome da clínica.',
            'crmv.required_if' => 'Informe seu CRMV.',
        ]);

        $user = User::create($data);
        Auth::login($user, self::fromApp($request));
        $request->session()->regenerate();

        return $user->isVet()
            ? redirect()->route('vet.index')->with('success', 'Bem-vindo(a) ao PetDay! Sua agenda está pronta.')
            : redirect()->route('pets.create')->with('success', 'Conta criada! Agora cadastre seu primeiro pet 🐾');
    }

    /** O app Android identifica-se no User-Agent; nele a sessão fica lembrada para as notificações em segundo plano. */
    private static function fromApp(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), 'PetDayApp');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
