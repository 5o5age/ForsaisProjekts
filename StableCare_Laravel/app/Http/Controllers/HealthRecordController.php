<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Horse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class HealthRecordController extends Controller
{
    public function index(Request $request): View
    {
        $query = HealthRecord::whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))->with(['horse', 'veterinarian'])->orderByDesc('record_date');
        if ($request->filled('horse_id')) $query->where('horse_id', $request->integer('horse_id'));
        return view('health.index', ['records' => $query->paginate(15)->withQueryString(), 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]);
    }
    public function create(): View { abort_unless(auth()->user()->canManageHealth(), 403); return view('health.form', ['record' => new HealthRecord(), 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { abort_unless(auth()->user()->canManageHealth(), 403); HealthRecord::create($this->validated($request) + ['veterinarian_id' => auth()->id()]); return redirect()->route('health.index')->with('success', 'Veselības ieraksts pievienots.'); }
    public function edit(HealthRecord $record): View { abort_unless(auth()->user()->canManageHealth(), 403); $this->authorizeStable($record); return view('health.form', ['record' => $record, 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]); }
    public function update(Request $request, HealthRecord $record): RedirectResponse { abort_unless(auth()->user()->canManageHealth(), 403); $this->authorizeStable($record); $record->update($this->validated($request)); return redirect()->route('health.index')->with('success', 'Veselības ieraksts atjaunināts.'); }
    public function destroy(HealthRecord $record): RedirectResponse { abort_unless(auth()->user()->canManageHealth(), 403); $this->authorizeStable($record); $record->delete(); return redirect()->route('health.index')->with('success', 'Veselības ieraksts dzēsts.'); }

    private function authorizeStable(HealthRecord $record): void { abort_unless($record->horse && $record->horse->stable_id !== null && $record->horse->stable_id === auth()->user()->stable_id, 404); }

    private function validated(Request $request): array
    {
        return $request->validate([
            'horse_id' => ['required', 'integer', Rule::exists('horses', 'id')->where('stable_id', auth()->user()->stable_id)], 'record_type' => ['required', Rule::in(['Apskate', 'Vakcinācija', 'Ārstēšana', 'Zobārsts', 'Kalšana', 'Analīzes', 'Cits'])],
            'record_date' => ['required', 'date'], 'title' => ['required', 'string', 'max:180'], 'diagnosis' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:10000'], 'next_date' => ['nullable', 'date'],
        ]);
    }
}
