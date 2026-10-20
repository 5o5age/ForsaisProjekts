<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View { return view('profile.edit'); }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:30'],
            'last_name' => ['required', 'string', 'min:1', 'max:30'],
            'email' => ['required', 'email', 'max:50', Rule::unique('users', 'email')->ignore(auth()->id())],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $user = $request->user();
        $email = $data['email'];
        if ($user->email !== $email) $user->email_verified_at = null;
        $user->fill([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
        ])->save();
        return back()->with('success', 'Profila dati saglabāti.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]], [
            'current_password.current_password' => 'Pašreizējā parole nav pareiza.', 'password.confirmed' => 'Jaunā parole nesakrīt.',
        ]);
        $request->user()->update(['password' => $data['password']]);
        return back()->with('success', 'Parole veiksmīgi nomainīta.');
    }
}
