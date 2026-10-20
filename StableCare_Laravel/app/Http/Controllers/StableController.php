<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StableController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $stable = $request->user()->stable;
        abort_unless($stable, 404, 'Staļļa informācija nav atrasta.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:150'],
        ]);
        $stable->update($data);
        return back()->with('success', 'Staļļa informācija saglabāta.');
    }

    public function regenerateInviteCode(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $stable = $request->user()->stable;
        abort_unless($stable, 404, 'Staļļa informācija nav atrasta.');
        $stable->update(['invite_code' => Str::upper(Str::random(24))]);
        return back()->with('success', 'Izveidots jauns staļļa ielūguma kods. Vecais kods vairs nedarbojas.');
    }
}
