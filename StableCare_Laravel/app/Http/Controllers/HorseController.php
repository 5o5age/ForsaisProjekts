<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class HorseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Horse::where('stable_id', auth()->user()->stable_id)->withCount(['tasks', 'trainings', 'healthRecords'])->orderBy('name');
        if ($request->filled('q')) {
            $needle = $request->string('q')->toString();
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$needle.'%')->orWhere('breed', 'like', '%'.$needle.'%')->orWhere('chip_number', 'like', '%'.$needle.'%'));
        }
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        $horses = $query->paginate(12)->withQueryString();
        return view('horses.index', compact('horses'));
    }

    public function show(Horse $horse): View
    {
        $this->authorizeStable($horse);
        $horse->load([
            'feedings' => fn ($q) => $q->where('active', true)->with('inventoryItem')->orderBy('feed_time'),
            'healthRecords' => fn ($q) => $q->with('veterinarian')->orderByDesc('record_date')->limit(5),
            'trainings' => fn ($q) => $q->with('trainer')->orderByDesc('training_date')->limit(5),
            'tasks' => fn ($q) => $q->with('assignee')->whereDate('start_date', '<=', now()->toDateString())->where(fn ($where) => $where->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString()))->orderBy('task_time')->limit(8),
            'documents' => fn ($q) => $q->with('uploader')->latest(),
            'horseNotes' => fn ($q) => $q->with('author')->latest()->limit(10),
        ]);
        return view('horses.show', compact('horse'));
    }

    public function create(): View { abort_unless(auth()->user()->canManageHorseProfile(), 403); return view('horses.form', ['horse' => new Horse()]); }
    public function store(Request $request): RedirectResponse { abort_unless(auth()->user()->canManageHorseProfile(), 403); $horse = Horse::create($this->validated($request) + ['stable_id' => auth()->user()->stable_id, 'created_by' => auth()->id()]); return redirect()->route('horses.index')->with('success', 'Zirgs “'.$horse->name.'” pievienots.'); }
    public function edit(Horse $horse): View { abort_unless(auth()->user()->canManageHorseProfile(), 403); $this->authorizeStable($horse); return view('horses.form', compact('horse')); }
    public function update(Request $request, Horse $horse): RedirectResponse { abort_unless(auth()->user()->canManageHorseProfile(), 403); $this->authorizeStable($horse); $horse->update($this->validated($request, $horse)); return redirect()->route('horses.index')->with('success', 'Zirga dati saglabāti.'); }
    public function destroy(Horse $horse): RedirectResponse { abort_unless(auth()->user()->canManageHorseProfile(), 403); $this->authorizeStable($horse); foreach ($horse->documents as $document) $document->delete(); $horse->delete(); return redirect()->route('horses.index')->with('success', 'Zirgs dzēsts.'); }

    private function authorizeStable(Horse $horse): void { abort_unless($horse->stable_id !== null && $horse->stable_id === auth()->user()->stable_id, 404); }

    private function validated(Request $request, ?Horse $horse = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:30'], 'owner_name' => ['required', 'string', 'max:100'], 'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'breed' => ['nullable', 'string', 'max:50'], 'sex' => ['nullable', Rule::in(['Ķēve', 'Ērzelis', 'Ruinīts', 'Nezināms'])],
            'colour' => ['nullable', 'string', 'max:80'], 'chip_number' => ['nullable', 'string', 'max:80', Rule::unique('horses', 'chip_number')->ignore($horse?->id)],
            'weight_kg' => ['required', 'numeric', 'min:1', 'max:2000'], 'status' => ['required', Rule::in(['active', 'rest', 'retired'])], 'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
