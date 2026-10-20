<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $query = User::query()->where('stable_id', auth()->user()->stable_id)->orderBy('name');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->toString().'%')->orWhere('email', 'like', '%'.$request->string('q')->toString().'%'));
        if ($request->filled('role')) $query->where('role', $request->string('role')->toString());
        return view('users.index', ['users' => $query->paginate(15)->withQueryString(), 'roles' => User::ROLE_LABELS]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return view('users.form', ['roles' => User::ROLE_LABELS, 'user' => new User()]);
    }


    public function edit(User $user): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($user->stable_id === auth()->user()->stable_id, 404);
        return view('users.form', ['roles' => User::ROLE_LABELS, 'user' => $user]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:50', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', 'max:128', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ]);
        User::create([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => true,
            'stable_id' => auth()->user()->stable_id,
        ]);
        return redirect()->route('users.index')->with('success', 'Jauns lietotājs pievienots stallim.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($user->stable_id === auth()->user()->stable_id, 404);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:30'],
            'last_name' => ['required', 'string', 'min:1', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:50', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'confirmed', 'max:128', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ]);
        if ($user->isAdmin() && $data['role'] !== 'admin' && User::where('stable_id', auth()->user()->stable_id)->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Sistēmā jābūt vismaz vienam administratoram.'])->withInput();
        }
        $update = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
        ];
        if ($user->email !== $data['email']) $update['email_verified_at'] = null;
        if (!empty($data['password'])) $update['password'] = $data['password'];
        $user->update($update);
        return redirect()->route('users.index')->with('success', 'Lietotāja informācija atjaunināta.');
    }

    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($user->stable_id === auth()->user()->stable_id, 404);
        $data = $request->validate(['role' => ['required', Rule::in(User::ROLES)], 'is_active' => ['required', 'boolean']]);
        if ($user->isAdmin() && $data['role'] !== 'admin' && User::where('stable_id', auth()->user()->stable_id)->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Sistēmā jābūt vismaz vienam administratoram.']);
        }
        if ($user->id === auth()->id() && !$data['is_active']) return back()->withErrors(['is_active' => 'Nevari deaktivizēt savu kontu, kamēr esi tajā pieslēdzies.']);
        $user->update($data);
        return back()->with('success', 'Lietotāja loma un konta statuss atjaunināts.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($user->stable_id === auth()->user()->stable_id, 404);
        if ($user->id === auth()->id()) return back()->withErrors(['user' => 'Nevari dzēst savu kontu.']);
        if ($user->isAdmin() && User::where('stable_id', auth()->user()->stable_id)->where('role', 'admin')->count() <= 1) return back()->withErrors(['user' => 'Sistēmā jābūt vismaz vienam administratoram.']);
        $user->delete();
        return back()->with('success', 'Lietotāja konts dzēsts.');
    }
}
