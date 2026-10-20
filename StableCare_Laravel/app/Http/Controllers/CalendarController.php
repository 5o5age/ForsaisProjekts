<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;

class CalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        try { $month = CarbonImmutable::createFromFormat('!Y-m', (string) $request->query('month', now()->format('Y-m'))); }
        catch (\Throwable) { $month = CarbonImmutable::today()->startOfMonth(); }
        if (!$month) $month = CarbonImmutable::today()->startOfMonth();
        $month = $month->startOfMonth();
        $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $hasTaskOccurrences = Schema::hasTable('task_occurrences');
        $taskQuery = Task::with(['horse', 'assignee', 'inventoryItem', 'completions']);
        if ($hasTaskOccurrences) $taskQuery->with('occurrences');
        $tasks = $taskQuery
            ->whereHas('horse', fn ($q) => $q->where('stable_id', auth()->user()->stable_id))
            ->where(function ($q) use ($gridStart, $gridEnd, $hasTaskOccurrences) {
                $q->where(fn ($base) => $base->whereDate('start_date', '<=', $gridEnd->toDateString())
                    ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $gridStart->toDateString())))
                    ->when($hasTaskOccurrences, fn ($query) => $query->orWhereHas('occurrences', fn ($occurrences) => $occurrences->whereBetween('scheduled_date', [$gridStart->toDateString(), $gridEnd->toDateString()])));
            })
            ->when(Schema::hasColumn('tasks', 'active'), fn ($query) => $query->where('active', true))
            ->orderBy('task_time')->get();
        if (!$hasTaskOccurrences) $tasks->each(fn (Task $task) => $task->setRelation('occurrences', collect()));
        $days = [];
        for ($date = $gridStart; $date->lte($gridEnd); $date = $date->addDay()) {
            $ds = $date->toDateString();
            $events = collect();
            foreach ($tasks as $task) {
                foreach ($task->originalDatesForScheduledDate($date) as $originalDate) {
                    $events->push(['task' => $task, 'done' => $task->completions->contains(fn ($completion) => $completion->occurrence_date->toDateString() === $originalDate), 'date' => $originalDate, 'displayDate' => $ds]);
                }
            }
            $days[] = ['date' => $date, 'inMonth' => $date->month === $month->month, 'isToday' => $ds === now()->toDateString(), 'tasks' => $events->sortBy(fn ($event) => $event['task']->task_time)->values()];
        }
        return view('calendar.index', ['month' => $month, 'days' => $days, 'prevMonth' => $month->subMonth()->format('Y-m'), 'nextMonth' => $month->addMonth()->format('Y-m')]);
    }
}
