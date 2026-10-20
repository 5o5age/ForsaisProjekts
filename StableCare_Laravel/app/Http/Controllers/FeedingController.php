<?php

namespace App\Http\Controllers;

use App\Models\Feeding;
use App\Models\Horse;
use App\Models\InventoryItem;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FeedingController extends Controller
{
    public function index(): View
    {
        return view('feedings.index', ['feedings' => Feeding::whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))->with(['horse', 'inventoryItem'])->orderBy('feed_time')->paginate(15)]);
    }

    public function create(): View { abort_unless(auth()->user()->canManageTasks(), 403); return view('feedings.form', ['feeding' => new Feeding(), 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'inventory' => InventoryItem::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]); }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        DB::transaction(function () use ($request, &$feeding) {
            $feeding = Feeding::create($this->validated($request) + ['created_by' => auth()->id()]);
            $this->syncTask($feeding);
        });
        return redirect()->route('feedings.index')->with('success', 'Barošanas plāns saglabāts un pievienots kalendāram.');
    }

    public function edit(Feeding $feeding): View
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        $this->authorizeStable($feeding);
        return view('feedings.form', ['feeding' => $feeding, 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'inventory' => InventoryItem::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]);
    }

    public function update(Request $request, Feeding $feeding): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        $this->authorizeStable($feeding);
        DB::transaction(function () use ($request, $feeding) {
            $feeding->update($this->validated($request));
            $this->syncTask($feeding);
        });
        return redirect()->route('feedings.index')->with('success', 'Barošanas plāns atjaunināts.');
    }

    public function destroy(Feeding $feeding): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        $this->authorizeStable($feeding);
        DB::transaction(fn () => $feeding->delete());
        return redirect()->route('feedings.index')->with('success', 'Barošanas plāns dzēsts.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'horse_id' => ['required', 'integer', Rule::exists('horses', 'id')->where('stable_id', auth()->user()->stable_id)], 'name' => ['required', 'string', 'max:120'],
            'inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')->where('stable_id', auth()->user()->stable_id)], 'amount' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'feed_time' => ['required', 'date_format:H:i'], 'recurrence' => ['required', Rule::in(['once','daily','weekdays','weekends','custom_weekdays','month_ranges'])],
            'weekdays' => ['nullable', 'array'], 'weekdays.*' => ['integer', 'between:1,7'], 'month_ranges' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'active' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string', 'max:10000'],
        ]);
        if ($data['recurrence'] === 'custom_weekdays' && empty($data['weekdays'])) return throw \Illuminate\Validation\ValidationException::withMessages(['weekdays' => 'Izvēlies vismaz vienu nedēļas dienu.']);
        if ($data['recurrence'] === 'month_ranges' && !$this->validRanges((string) ($data['month_ranges'] ?? ''))) return throw \Illuminate\Validation\ValidationException::withMessages(['month_ranges' => 'Norādi derīgus intervālus, piemēram, 1-5, 6-7.']);
        $data['weekdays'] = array_values(array_unique(array_map('intval', $data['weekdays'] ?? [])));
        $data['active'] = $request->boolean('active');
        if (empty($data['inventory_item_id'])) $data['inventory_item_id'] = null;
        return $data;
    }

    private function authorizeStable(Feeding $feeding): void { abort_unless($feeding->horse && $feeding->horse->stable_id !== null && $feeding->horse->stable_id === auth()->user()->stable_id, 404); }

    private function syncTask(Feeding $feeding): void
    {
        $task = $feeding->task;
        if (!$feeding->active) { $task?->update(['active' => false]); return; }
        $task ??= new Task();
        $task->fill([
            'title' => 'Barošana: '.$feeding->name,
            'task_type' => 'feeding', 'horse_id' => $feeding->horse_id, 'feeding_id' => $feeding->id,
            'start_date' => $feeding->start_date->toDateString(), 'task_time' => $feeding->feed_time,
            'priority' => 'normal', 'recurrence' => $feeding->recurrence, 'weekdays' => $feeding->weekdays ?? [],
            'month_ranges' => $feeding->month_ranges, 'end_date' => $feeding->end_date?->toDateString(),
            'inventory_item_id' => $feeding->inventory_item_id, 'consumption' => $feeding->inventory_item_id ? $feeding->amount : null,
            'notes' => $feeding->notes, 'created_by' => $feeding->created_by,
            'active' => true,
        ]);
        $task->save();
    }

    private function validRanges(string $value): bool
    {
        if (trim($value) === '') return false;
        foreach (preg_split('/[,;]/', $value) ?: [] as $part) {
            if (!preg_match('/^\s*(\d{1,2})\s*(?:[-–—]\s*(\d{1,2}))?\s*$/', $part, $m)) return false;
            $a = (int) $m[1]; $b = isset($m[2]) ? (int) $m[2] : $a;
            if ($a < 1 || $b > 31 || $a > $b) return false;
        }
        return true;
    }
}
