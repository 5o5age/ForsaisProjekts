<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'surname' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'regex:/\.[a-z]{2,}$/i', 'max:50', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Ievadi vārdu.',
            'name.max' => 'Vārds nedrīkst būt garāks par 30 simboliem.',
            'surname.required' => 'Ievadi uzvārdu.',
            'surname.max' => 'Uzvārds nedrīkst būt garāks par 30 simboliem.',
            'email.required' => 'Ievadi e-pasta adresi.',
            'email.email' => 'Ievadi derīgu e-pasta adresi.',
            'email.regex' => 'E-pasta adresei jābeidzas ar domēna galotni, piemēram, .lv vai .com.',
            'email.max' => 'E-pasta adrese nedrīkst būt garāka par 50 simboliem.',
            'email.unique' => 'Šī e-pasta adrese jau ir reģistrēta.',
            'password.required' => 'Ievadi paroli.',
            'password.min' => 'Parolei jābūt vismaz 8 simbolus garai.',
            'password.letters' => 'Parolei jāsatur vismaz viens burts.',
            'password.numbers' => 'Parolei jāsatur vismaz viens cipars.',
        ]);

        User::create($validated);

        return to_route('login')->with('status', 'Reģistrācija veiksmīga! Vari pieslēgties.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'regex:/\.[a-z]{2,}$/i', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ievadi e-pasta adresi.',
            'email.email' => 'Ievadi derīgu e-pasta adresi.',
            'email.regex' => 'E-pasta adresei jābeidzas ar domēna galotni, piemēram, .lv vai .com.',
            'password.required' => 'Ievadi paroli.',
        ]);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return back()
                ->withErrors(['email' => 'Nepareizs e-pasts vai parole.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return to_route('home');
    }

    public function dashboard(): View
    {
        return view('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}