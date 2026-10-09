<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'bio' => ['nullable', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:80'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'clinic_name' => ['nullable', 'string', 'max:120'],
            'crmv' => ['nullable', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:80'],
            'clinic_address' => ['nullable', 'string', 'max:160'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $data['avatar'] = $this->storeImage($request->file('avatar'), 'avatars', $user->avatar);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'Conta atualizada!');
    }
}
