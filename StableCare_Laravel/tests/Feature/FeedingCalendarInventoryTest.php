<?php

namespace Tests\Feature;

use App\Models\Horse;
use App\Models\InventoryItem;
use App\Models\Stable;
use App\Models\Task;
use App\Models\User;
use App\Services\InventoryForecastService;
use App\Services\TaskCompletionService;
use App\Services\TaskOccurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedingCalendarInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Stable $stable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Aprūpes admins', 'email' => 'care@example.test', 'password' => 'StableCare12345', 'role' => 'admin', 'is_active' => true]);
        $this->stable = Stable::create(['name' => 'Testu stallis', 'invite_code' => 'TEST-INVITE']);
        $this->user->update(['stable_id' => $this->stable->id]);
    }

    public function test_recurrence_covers_weekdays_weekends_and_selected_days(): void
    {
        $weekdayTask = new Task(['recurrence' => 'weekdays', 'start_date' => '2026-10-19']);
        $weekendTask = new Task(['recurrence' => 'weekends', 'start_date' => '2026-10-19']);
        $selectedTask = new Task(['recurrence' => 'custom_weekdays', 'weekdays' => [1, 3, 5], 'start_date' => '2026-10-19']);

        $this->assertTrue($weekdayTask->occursOn(CarbonImmutable::parse('2026-10-19')));
        $this->assertFalse($weekdayTask->occursOn(CarbonImmutable::parse('2026-10-24')));
        $this->assertTrue($weekendTask->occursOn(CarbonImmutable::parse('2026-10-25')));
        $this->assertTrue($selectedTask->occursOn(CarbonImmutable::parse('2026-10-21')));
        $this->assertFalse($selectedTask->occursOn(CarbonImmutable::parse('2026-10-22')));
    }

    public function test_moving_one_occurrence_does_not_change_other_dates_and_future_scope_moves_each_occurrence(): void
    {
        $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Māra']);
        $task = $this->task($horse, null, 2, 'daily', '2026-10-19');
        $service = app(TaskOccurrenceService::class);

        $service->move($task, '2026-10-20', '2026-10-21', 'single', $this->user->id);
        $this->assertDatabaseHas('task_occurrences', ['task_id' => $task->id, 'original_date' => '2026-10-20 00:00:00', 'scheduled_date' => '2026-10-21 00:00:00']);
        $this->assertDatabaseCount('task_occurrences', 1);
        $shiftedTask = $task->fresh();
        $this->assertTrue($shiftedTask->originalDatesForScheduledDate(CarbonImmutable::parse('2026-10-19'))->contains('2026-10-19'));
        $this->assertTrue($shiftedTask->originalDatesForScheduledDate(CarbonImmutable::parse('2026-10-21'))->contains('2026-10-20'));

        $service->move($task, '2026-10-21', '2026-10-23', 'future', $this->user->id);
        $this->assertDatabaseHas('task_occurrences', ['task_id' => $task->id, 'original_date' => '2026-10-21 00:00:00', 'scheduled_date' => '2026-10-23 00:00:00', 'scope' => 'future']);
        $this->assertTrue($task->fresh()->originalDatesForScheduledDate(CarbonImmutable::parse('2026-10-24'))->contains('2026-10-22'));
        $movedTask = $task->fresh();
        $this->assertFalse($movedTask->originalDatesForScheduledDate(CarbonImmutable::parse('2026-10-21'))->contains('2026-10-21'));
        $this->assertTrue($movedTask->originalDatesForScheduledDate(CarbonImmutable::parse('2036-10-20'))->contains('2036-10-18'));
    }

    public function test_calendar_shows_occurrences_and_the_two_move_scopes(): void
    {
        $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Māra']);
        $this->task($horse, null, 2, 'daily', '2026-10-20', '2026-10-22');

        $this->actingAs($this->user)->get('/kalendars?month=2026-10')
            ->assertOk()
            ->assertSee('Barošana')
            ->assertSee('Tikai šo reizi')
            ->assertSee('Šo un turpmākās reizes');
    }

    public function test_dashboard_renders_with_recurring_feeding_for_today(): void
    {
        $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Māra']);
        $this->task($horse, null, 2, 'daily', now()->toDateString());

        $this->actingAs($this->user)->get('/dashboard')->assertOk()->assertSee('Barošana');
    }

    public function test_several_feeding_plans_per_horse_create_separate_calendar_meals(): void
    {
        $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Māra']);
        $item = InventoryItem::create(['stable_id' => $this->stable->id, 'name' => 'Auzas', 'category' => 'Barība', 'quantity' => 100, 'unit' => 'kg', 'minimum_quantity' => 10]);
        $this->actingAs($this->user);

        foreach (['08:00', '13:00', '18:00'] as $time) {
            $this->post('/barosana', [
                'name' => 'Auzas '.$time, 'horse_id' => $horse->id, 'inventory_item_id' => $item->id,
                'amount' => 2, 'feed_time' => $time, 'recurrence' => 'daily', 'start_date' => now()->toDateString(),
                'active' => '1', 'weekdays' => [],
            ])->assertRedirect('/barosana');
        }

        $this->assertDatabaseCount('feedings', 3);
        $this->assertDatabaseCount('tasks', 3);
        $this->get('/kalendars?month='.now()->format('Y-m'))->assertOk()
            ->assertSee('08:00')->assertSee('13:00')->assertSee('18:00');
    }

    public function test_completion_deducts_stock_once_and_uncompletion_restores_it(): void
    {
        $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Māra']);
        $item = InventoryItem::create(['stable_id' => $this->stable->id, 'name' => 'Auzas', 'category' => 'Barība', 'quantity' => 100, 'unit' => 'kg', 'minimum_quantity' => 10]);
        $task = $this->task($horse, $item, 2, 'daily', '2026-10-19');
        $service = app(TaskCompletionService::class);

        $service->setCompleted($task, '2026-10-20', true, $this->user->id, 'Pabaroja');
        $service->setCompleted($task, '2026-10-20', true, $this->user->id, 'Piezīme atjaunināta');
        $this->assertSame('98.000', $item->fresh()->quantity);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('task_completions', ['task_id' => $task->id, 'occurrence_date' => '2026-10-20 00:00:00', 'notes' => 'Piezīme atjaunināta']);

        $service->setCompleted($task, '2026-10-20', false, $this->user->id);
        $this->assertSame('100.000', $item->fresh()->quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_forecast_sums_all_meals_and_finds_first_unaffordable_day(): void
    {
        $item = InventoryItem::create(['stable_id' => $this->stable->id, 'name' => 'Auzas', 'category' => 'Barība', 'quantity' => 100, 'unit' => 'kg', 'minimum_quantity' => 10]);
        $tasks = collect();
        for ($horseNumber = 1; $horseNumber <= 5; $horseNumber++) {
            $horse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Zirgs '.$horseNumber]);
            foreach (['08:00:00', '13:00:00', '18:00:00'] as $time) {
                $task = $this->task($horse, $item, 2, 'daily', '2026-10-20');
                $task->task_time = $time;
                $tasks->push($task);
            }
        }
        $inactiveHorse = Horse::create(['stable_id' => $this->stable->id, 'name' => 'Neaktīvs zirgs']);
        $inactiveTask = $this->task($inactiveHorse, $item, 1000, 'daily', '2026-10-20');
        $inactiveTask->update(['active' => false]);
        $tasks->push($inactiveTask->fresh());

        $forecast = app(InventoryForecastService::class)->forecast($item, $tasks, CarbonImmutable::parse('2026-10-20'));

        $this->assertSame('2026-10-23', $forecast['depletion_date']->toDateString());
        $this->assertSame('2026-10-22', $forecast['minimum_date']->toDateString());
        $this->assertTrue($forecast['low']);
    }

    private function task(Horse $horse, ?InventoryItem $item, float $amount, string $recurrence, string $start, ?string $end = null): Task
    {
        return Task::create([
            'title' => 'Barošana', 'task_type' => 'feeding', 'horse_id' => $horse->id,
            'start_date' => $start, 'end_date' => $end, 'task_time' => '08:00:00',
            'priority' => 'normal', 'recurrence' => $recurrence, 'weekdays' => [],
            'inventory_item_id' => $item?->id, 'consumption' => $item ? $amount : null,
            'active' => true, 'created_by' => $this->user->id,
        ]);
    }
}
