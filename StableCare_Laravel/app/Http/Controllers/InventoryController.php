<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Task;
use App\Services\InventoryForecastService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    public function index(InventoryForecastService $forecastService): View
    {
        $items = InventoryItem::where('stable_id', auth()->user()->stable_id)->withCount('movements')->when(request('q'), fn ($q, $needle) => $q->where('name', 'like', '%'.request('q').'%'))->when(request()->boolean('low'), fn ($q) => $q->whereColumn('quantity', '<=', 'minimum_quantity'))->orderByRaw('(quantity <= minimum_quantity) DESC')->orderBy('name')->paginate(15);
        $lowCount = InventoryItem::where('stable_id', auth()->user()->stable_id)->whereColumn('quantity', '<=', 'minimum_quantity')->count();
        $today = CarbonImmutable::today();
        $forecastEnd = $today->addDays(365);
        $hasTaskOccurrences = Schema::hasTable('task_occurrences');
        $taskQuery = Task::with('completions');
        if ($hasTaskOccurrences) $taskQuery->with('occurrences');
        $tasks = $taskQuery->when(Schema::hasColumn('tasks', 'active'), fn ($query) => $query->where('active', true))
            ->whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))
            ->whereNotNull('inventory_item_id')
            ->where(fn ($q) => $q->where(fn ($base) => $base->whereDate('start_date', '<=', $forecastEnd->toDateString())
                ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $today->toDateString())))
                ->when($hasTaskOccurrences, fn ($query) => $query->orWhereHas('occurrences', fn ($occurrences) => $occurrences->whereBetween('scheduled_date', [$today->toDateString(), $forecastEnd->toDateString()])))
            )
            ->get();
        if (!$hasTaskOccurrences) $tasks->each(fn (Task $task) => $task->setRelation('occurrences', collect()));
        foreach ($items as $item) {
            $relevant = $tasks->where('inventory_item_id', $item->id)->filter(fn ($task) => (float) $task->consumption > 0)->values();
            if ($relevant->isEmpty()) {
                $item->setAttribute('forecast_label', 'Nav plÄnota patÄ“riÅ†a');
                continue;
            }
            $forecast = $forecastService->forecast($item, $relevant, $today);
            $item->setAttribute('forecast_label', $forecast['label']);
            $item->setAttribute('forecast_minimum_date', $forecast['minimum_date']);
            $item->setAttribute('forecast_low', $forecast['low']);
        }
        return view('inventory.index', ['items' => $items, 'lowCount' => $lowCount]);
    }
    public function create(): View { abort_unless(auth()->user()->canManageInventory(), 403); return view('inventory.form', ['item' => new InventoryItem()]); }
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canManageInventory(), 403);
        $data = $this->validated($request);
        DB::transaction(function () use ($data, &$item) {
            $item = InventoryItem::create($data + ['stable_id' => auth()->user()->stable_id, 'created_by' => auth()->id()]);
            if ((float) $item->quantity !== 0.0) InventoryMovement::create(['inventory_item_id' => $item->id, 'user_id' => auth()->id(), 'quantity_delta' => $item->quantity, 'reason' => 'SÄkuma atlikums']);
        });
        return redirect()->route('inventory.index')->with('success', 'KrÄjums pievienots.');
    }
    public function edit(InventoryItem $item): View { abort_unless(auth()->user()->canManageInventory(), 403); $this->authorizeStable($item); return view('inventory.form', compact('item')); }
    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        abort_unless(auth()->user()->canManageInventory(), 403);
        $this->authorizeStable($item);
        $data = $this->validated($request, $item);
        DB::transaction(function () use ($data, $item) {
            $before = (float) $item->quantity;
            $item->update($data);
            $delta = round((float) $item->quantity - $before, 3);
            if (abs($delta) > 0.00001) InventoryMovement::create(['inventory_item_id' => $item->id, 'user_id' => auth()->id(), 'quantity_delta' => $delta, 'reason' => 'ManuÄla atlikuma korekcija']);
        });
        return redirect()->route('inventory.index')->with('success', 'KrÄjuma atlikums atjauninÄts.');
    }
    public function destroy(InventoryItem $item): RedirectResponse { abort_unless(auth()->user()->canManageInventory(), 403); $this->authorizeStable($item); $item->delete(); return redirect()->route('inventory.index')->with('success', 'KrÄjums dzÄ“sts.'); }

    public function movements(InventoryItem $item): View
    {
        abort_unless(auth()->user()->canManageInventory(), 403);
        $this->authorizeStable($item);
        $movements = InventoryMovement::with('user')->where('inventory_item_id', $item->id)->orderByDesc('created_at')->paginate(20);
        return view('inventory.movements', compact('item', 'movements'));
    }

    private function authorizeStable(InventoryItem $item): void { abort_unless($item->stable_id !== null && $item->stable_id === auth()->user()->stable_id, 404); }

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:140', Rule::unique('inventory_items', 'name')->where('stable_id', auth()->user()->stable_id)->ignore($item?->id)],
            'category' => ['required', 'string', 'max:80'], 'quantity' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'unit' => ['required', Rule::in(['kg', 'g', 'l', 'ml', 'gab.', 'iepakojumi'])],
            'minimum_quantity' => ['required', 'numeric', 'min:0', 'max:999999999'], 'location' => ['nullable', 'string', 'max:140'], 'expires_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}

