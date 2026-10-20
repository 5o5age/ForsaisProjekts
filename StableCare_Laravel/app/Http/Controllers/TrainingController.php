<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function index(): View { return view('trainings.index', ['trainings' => Training::whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))->with(['horse', 'trainer'])->orderByDesc('training_date')->orderByDesc('training_time')->paginate(15)]); }
    public function create(): View { abort_unless(auth()->user()->canManageTraining(), 403); return view('trainings.form', ['training' => new Training(), 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'trainers' => User::where('stable_id', auth()->user()->stable_id)->whereIn('role', ['trainer','admin'])->where('is_active', true)->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { abort_unless(auth()->user()->canManageTraining(), 403); Training::create($this->validated($request) + ['trainer_id' => auth()->id()]); return redirect()->route('trainings.index')->with('success', 'Treniņš ieplānots.'); }
    public function edit(Training $training): View { abort_unless(auth()->user()->canManageTraining(), 403); $this->authorizeStable($training); return view('trainings.form', ['training' => $training, 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'trainers' => User::where('stable_id', auth()->user()->stable_id)->whereIn('role', ['trainer','admin'])->where('is_active', true)->orderBy('name')->get()]); }
    public function update(Request $request, Training $training): RedirectResponse { abort_unless(auth()->user()->canManageTraining(), 403); $this->authorizeStable($training); $training->update($this->validated($request)); return redirect()->route('trainings.index')->with('success', 'Treniņš atjaunināts.'); }
    public function destroy(Training $training): RedirectResponse { abort_unless(auth()->user()->canManageTraining(), 403); $this->authorizeStable($training); $training->delete(); return redirect()->route('trainings.index')->with('success', 'Treniņš dzēsts.'); }
    private function authorizeStable(Training $training): void { abort_unless($training->horse && $training->horse->stable_id !== null && $training->horse->stable_id === auth()->user()->stable_id, 404); }

    private function validated(Request $request): array
    {
        return $request->validate([
            'horse_id' => ['required', 'integer', Rule::exists('horses', 'id')->where('stable_id', auth()->user()->stable_id)], 'training_date' => ['required', 'date'], 'training_time' => ['nullable', 'date_format:H:i'],
            'training_type' => ['required', 'string', 'max:100'], 'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'status' => ['required', Rule::in(['planned', 'completed', 'cancelled'])], 'notes' => ['nullable', 'string', 'max:10000'], 'results' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
