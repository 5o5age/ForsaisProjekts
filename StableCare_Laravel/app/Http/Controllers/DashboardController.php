<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Horse;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\Training;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = CarbonImmutable::today();
        $user = auth()->user();
        $tasks = collect();

        if ($user->canManageTasks()) {
            $hasActiveColumn = Schema::hasColumn('tasks', 'active');
            $hasTaskOccurrences = Schema::hasTable('task_occurrences');

            $taskQuery = Task::with(['horse', 'assignee', 'inventoryItem']);
            if ($hasTaskOccurrences) {
                $taskQuery->with('occurrences');
            }
            if ($hasActiveColumn) {
                $taskQuery->where('active', true);
            }

            $tasks = $taskQuery
                ->whereHas('horse', fn ($query) => $query->where('stable_id', $user->stable_id))
                ->whereDate('start_date', '<=', $today->toDateString())
                ->where(function ($query) use ($today) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $today->toDateString());
                })
                ->get()
                ->each(function (Task $task) use ($hasTaskOccurrences) {
                    if (!$hasTaskOccurrences) {
                        $task->setRelation('occurrences', collect());
                    }
                })
                ->flatMap(function (Task $task) use ($today) {
                    return $task->originalDatesForScheduledDate($today)->map(function (string $originalDate) use ($task) {
                        $occurrenceTask = clone $task;
                        $occurrenceTask->setAttribute('occurrence_date', $originalDate);

                        return $occurrenceTask;
                    });
                })
                ->sortBy('task_time')
                ->take(8)
                ->values();
        }

        return view('dashboard', [
            'horseCount' => Horse::where('stable_id', $user->stable_id)->count(),
            'todayTaskCount' => $user->canManageTasks() ? $tasks->count() : null,
            'doneCount' => $user->canManageTasks()
                ? $tasks->filter(fn (Task $task) => $task->isCompleteOn($task->occurrence_date))->count()
                : null,
            'lowStock' => $user->isAdmin() || $user->isSupervisor()
                ? InventoryItem::where('stable_id', $user->stable_id)
                    ->whereColumn('quantity', '<=', 'minimum_quantity')
                    ->orderBy('quantity')
                    ->limit(5)
                    ->get()
                : collect(),
            'expiringStock' => $user->isAdmin() || $user->isSupervisor()
                ? InventoryItem::where('stable_id', $user->stable_id)
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<=', $today->addDays(30)->toDateString())
                    ->orderBy('expires_at')
                    ->limit(5)
                    ->get()
                : collect(),
            'healthDue' => $user->canManageHealth() || $user->isAdmin()
                ? HealthRecord::whereHas('horse', fn ($query) => $query->where('stable_id', $user->stable_id))
                    ->with('horse')
                    ->whereNotNull('next_date')
                    ->whereDate('next_date', '<=', $today->addDays(30)->toDateString())
                    ->orderBy('next_date')
                    ->limit(5)
                    ->get()
                : collect(),
            'upcomingTraining' => $user->canManageTraining()
                ? Training::whereHas('horse', fn ($query) => $query->where('stable_id', $user->stable_id))
                    ->with(['horse', 'trainer'])
                    ->where('status', 'planned')
                    ->whereDate('training_date', '>=', $today->toDateString())
                    ->orderBy('training_date')
                    ->limit(5)
                    ->get()
                : collect(),
            'todayTasks' => $tasks,
        ]);
    }
}