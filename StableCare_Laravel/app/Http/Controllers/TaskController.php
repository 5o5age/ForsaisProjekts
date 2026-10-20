<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Services\TaskCompletionService;
use App\Services\TaskOccurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['feeding', 'cleaning', 'care', 'medicine', 'other'])],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['planned', 'done'])],
        ]);
        $selectedDate = CarbonImmutable::parse($filters['date'] ?? now()->toDateString())->startOfDay();
        $query = Task::with(['horse', 'assignee', 'inventoryItem', 'feeding', 'completions'])
            ->when(Schema::hasColumn('tasks', 'active'), fn ($query) => $query->where('active', true))
            ->whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))
            ->whereDate('start_date', '<=', $selectedDate->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate->toDateString()))
            ->when(!empty($filters['q']), fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%'))
            ->when(!empty($filters['type']), fn ($q) => $q->where('task_type', $filters['type']))
            ->orderBy('task_time');
        $rows = $query->get()->filter(function (Task $task) use ($selectedDate, $filters) {
            if (!$task->occursOn($selectedDate)) return false;
            $done = $task->completions->contains(fn ($c) => $c->occurrence_date->toDateString() === $selectedDate->toDateString());
            if (($filters['status'] ?? null) === 'done' && !$done) return false;
            if (($filters['status'] ?? null) === 'planned' && $done) return false;
            return true;
        })->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $tasks = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
        $today = $selectedDate->toDateString();
        return view('tasks.index', compact('tasks', 'today'));
    }

    public function create(): View { abort_unless(auth()->user()->canManageTasks(), 403); return view('tasks.form', ['task' => new Task(), 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'users' => \App\Models\User::where('stable_id', auth()->user()->stable_id)->where('is_active', true)->whereIn('role', ['admin', 'supervisor'])->orderBy('name')->get(), 'inventory' => InventoryItem::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { abort_unless(auth()->user()->canManageTasks(), 403); $task = Task::create($this->validated($request) + ['created_by' => auth()->id()]); return redirect()->route('tasks.index')->with('success', 'Uzdevums pievienots.'); }
    public function edit(Task $task): View { abort_unless(auth()->user()->canManageTasks() && !$task->feeding_id, 403); $this->authorizeStable($task); return view('tasks.form', ['task' => $task, 'horses' => Horse::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get(), 'users' => \App\Models\User::where('stable_id', auth()->user()->stable_id)->where('is_active', true)->whereIn('role', ['admin', 'supervisor'])->orderBy('name')->get(), 'inventory' => InventoryItem::where('stable_id', auth()->user()->stable_id)->orderBy('name')->get()]); }
    public function update(Request $request, Task $task): RedirectResponse { abort_unless(auth()->user()->canManageTasks() && !$task->feeding_id, 403); $this->authorizeStable($task); $task->update($this->validated($request, $task)); return redirect()->route('tasks.index')->with('success', 'Uzdevums atjaunināts.'); }
    public function destroy(Task $task): RedirectResponse { abort_unless(auth()->user()->canManageTasks() && !$task->feeding_id, 403); $this->authorizeStable($task); $task->delete(); return redirect()->route('tasks.index')->with('success', 'Uzdevums dzēsts.'); }

    public function moveOccurrence(Request $request, Task $task, TaskOccurrenceService $service): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        abort_unless(Schema::hasTable('task_occurrences'), 503, 'Calendar move support requires the database migration.');
        $this->authorizeStable($task);
        $data = $request->validate([
            'date' => ['required', 'date'],
            'new_date' => ['required', 'date', 'after_or_equal:today'],
            'scope' => ['required', Rule::in(['single', 'future'])],
        ]);
        $service->move($task, $data['date'], $data['new_date'], $data['scope'], auth()->id());
        return back()->with('success', $data['scope'] === 'single' ? 'Šī reize pārvietota.' : 'Šī un turpmākās reizes pārvietotas.');
    }

    public function complete(Request $request, Task $task, TaskCompletionService $service): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTasks(), 403);
        $this->authorizeStable($task);
        $data = $request->validate(['date' => ['required', 'date', 'before_or_equal:today'], 'notes' => ['nullable', 'string', 'max:500']]);
        $done = $request->boolean('completed');
        $service->setCompleted($task, $data['date'], $done, auth()->id(), $data['notes'] ?? null);
        return back()->with('success', $done ? 'Uzdevums atzīmēts kā paveikts.' : 'Uzdevums atzīmēts kā nepaveikts.');
    }

    private function authorizeStable(Task $task): void { abort_unless($task->horse && $task->horse->stable_id !== null && $task->horse->stable_id === auth()->user()->stable_id, 404); }

    private function validated(Request $request, ?Task $task = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'task_type' => ['required', Rule::in(['feeding', 'cleaning', 'care', 'medicine', 'other'])],
            'horse_id' => ['required', 'integer', Rule::exists('horses', 'id')->where('stable_id', auth()->user()->stable_id)], 'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('stable_id', auth()->user()->stable_id)->whereIn('role', ['admin', 'supervisor']))],
            'start_date' => ['required', 'date'], 'task_time' => ['nullable', 'date_format:H:i'], 'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'recurrence' => ['required', Rule::in(['once', 'daily', 'weekdays', 'weekends', 'custom_weekdays', 'month_ranges'])],
            'weekdays' => ['nullable', 'array'], 'weekdays.*' => ['integer', 'between:1,7'],
            'month_ranges' => ['nullable', 'string', 'max:100'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')->where('stable_id', auth()->user()->stable_id)], 'consumption' => ['nullable', 'numeric', 'gt:0', 'max:100000'], 'notes' => ['nullable', 'string', 'max:10000'],
        ]);
        if (($data['recurrence'] ?? '') === 'custom_weekdays' && empty($data['weekdays'])) {
            return throw \Illuminate\Validation\ValidationException::withMessages(['weekdays' => 'Izvēlies vismaz vienu nedēļas dienu.']);
        }
        if (($data['recurrence'] ?? '') === 'month_ranges') {
            $ranges = trim((string) ($data['month_ranges'] ?? ''));
            if ($ranges === '' || !$this->validMonthRanges($ranges)) return throw \Illuminate\Validation\ValidationException::withMessages(['month_ranges' => 'Norādi derīgus intervālus, piemēram, 1-5, 6-7.']);
        }
        $data['weekdays'] = array_values(array_unique(array_map('intval', $data['weekdays'] ?? [])));
        if (!empty($data['inventory_item_id']) && empty($data['consumption'])) {
            return throw \Illuminate\Validation\ValidationException::withMessages(['consumption' => 'Norādi patēriņu vienā reizē, ja izvēlēts krājums.']);
        }
        if (empty($data['inventory_item_id'])) $data['consumption'] = null;
        return $data;
    }

    private function validMonthRanges(string $input): bool
    {
        foreach (preg_split('/[,;]/', $input) ?: [] as $part) {
            if (!preg_match('/^\s*(\d{1,2})\s*(?:[-–—]\s*(\d{1,2}))?\s*$/', $part, $m)) return false;
            $from = (int) $m[1]; $to = isset($m[2]) ? (int) $m[2] : $from;
            if ($from < 1 || $to > 31 || $from > $to) return false;
        }
        return true;
    }
}
