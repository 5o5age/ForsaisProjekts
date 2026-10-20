<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Models\HorseNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HorseNoteController extends Controller
{
    public function store(Request $request, Horse $horse): RedirectResponse
    {
        $this->authorizeHorse($horse);
        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);
        $horse->horseNotes()->create(['user_id' => $request->user()->id, 'body' => trim($data['body'])]);
        return back()->with('success', 'Piezīme pievienota zirga profilam.');
    }

    public function destroy(Request $request, Horse $horse, HorseNote $note): RedirectResponse
    {
        $this->authorizeHorse($horse);
        abort_unless($note->horse_id === $horse->id, 404);
        abort_unless($request->user()->isAdmin() || $note->user_id === $request->user()->id, 403);
        $note->delete();
        return back()->with('success', 'Piezīme dzēsta.');
    }

    private function authorizeHorse(Horse $horse): void
    {
        abort_unless($horse->stable_id !== null && $horse->stable_id === auth()->user()->stable_id, 404);
    }
}
