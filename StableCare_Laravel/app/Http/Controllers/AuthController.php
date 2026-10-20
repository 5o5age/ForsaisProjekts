<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View { return view('auth.login'); }
    public function showRegister(): View { return view('auth.register', ['roles' => User::ROLE_LABELS]); }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['nullable', 'boolean']]);
        $key = strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Pārāk daudz mēģinājumu. Mēģini vēlreiz pēc '.RateLimiter::availableIn($key).' sekundēm.']);
        }
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Norādītie piekļuves dati nav pareizi vai konts ir deaktivizēts.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'))->with('success', 'Prieks tevi redzēt atkal!');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:61'],
            'first_name' => ['required_without:name', 'nullable', 'string', 'min:1', 'max:30'],
            'last_name' => ['required_without:name', 'nullable', 'string', 'min:1', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:50', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', 'in:admin,supervisor,trainer,veterinarian'],
            'admin_invite_code' => ['nullable', 'string', 'max:200'],
            'stable_mode' => ['nullable', 'in:create,join'],
            'stable_name' => ['nullable', 'string', 'max:100', 'required_if:stable_mode,create'],
            'stable_address' => ['nullable', 'string', 'max:150'],
            'stable_invite_code' => ['nullable', 'string', 'max:64', 'required_if:stable_mode,join'],
            'password' => ['required', 'confirmed', 'max:128', PasswordRule::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'password.confirmed' => 'Paroles nesakrīt.',
            'password.min' => 'Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur burti un cipari.',
            'terms.accepted' => 'Lai reģistrētos, jāapstiprina lietošanas noteikumi.',
            'stable_name.required_if' => 'Ja veido jaunu stalli, norādi tā nosaukumu.',
            'stable_invite_code.required_if' => 'Ievadi sava staļļa ielūguma kodu.',
        ]);

        $firstName = trim((string) ($data['first_name'] ?? Str::before((string) ($data['name'] ?? ''), ' ')));
        $lastName = trim((string) ($data['last_name'] ?? Str::after((string) ($data['name'] ?? ''), ' ')));
        if ($firstName === '' || $lastName === '') {
            throw ValidationException::withMessages(['first_name' => 'Ievadi vārdu un uzvārdu atsevišķos laukos.']);
        }
        $fullName = trim($firstName.' '.$lastName);

        $mode = $data['stable_mode'] ?? 'create';
        $stable = null;
        if ($mode === 'join') {
            $stable = Stable::query()->where('invite_code', trim((string) $data['stable_invite_code']))->first();
            if (!$stable) {
                throw ValidationException::withMessages(['stable_invite_code' => 'Staļļa ielūguma kods nav atrasts. Pārbaudi to vai lūdz administratoram jaunu kodu.']);
            }
            if ($data['role'] === 'admin') {
                throw ValidationException::withMessages(['role' => 'Administratora tiesības var piešķirt tikai esošā staļļa administrators.']);
            }
        }

        $user = DB::transaction(function () use ($data, $mode, $stable, $firstName, $lastName, $fullName) {
            if ($mode === 'create') {
                $stable = Stable::create([
                    'name' => ($data['stable_name'] ?? '') ?: ($fullName.' staļlis'),
                    'address' => $data['stable_address'] ?? null,
                    'subscription_type' => 'free',
                    'invite_code' => Str::upper(Str::random(24)),
                ]);
            }
            $user = User::create([
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $mode === 'create' ? 'admin' : $data['role'],
                'password' => $data['password'],
                'is_active' => true,
                'stable_id' => $stable?->id,
            ]);
            if ($mode === 'create') $stable->update(['created_by' => $user->id]);
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', 'Konts veiksmīgi izveidots. Laipni lūgts StableCare!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Tu esi droši izgājis no sistēmas.');
    }
}
