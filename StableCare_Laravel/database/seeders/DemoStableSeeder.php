<?php

namespace Database\Seeders;

use App\Models\Feeding;
use App\Models\HealthRecord;
use App\Models\Horse;
use App\Models\HorseNote;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Stable;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoStableSeeder extends Seeder
{
    private const STABLE_NAME = 'StableCare Demonstracijas stallis';
    private const INVITE_CODE = 'STABLECARE-DEMO-2026';
    private const PASSWORD = 'StableDemo2026!';

    public function run(): void
    {
        DB::transaction(function (): void {
            $stable = Stable::firstOrCreate(
                ['invite_code' => self::INVITE_CODE],
                ['name' => self::STABLE_NAME, 'address' => 'Zalu iela 12, Riga', 'subscription_type' => 'free']
            );

            if ($stable->name !== self::STABLE_NAME) {
                throw new RuntimeException('The reserved demo invite code belongs to another stable. No demo data was created.');
            }

            $users = $this->usersFor($stable);
            $stable->update(['created_by' => $users['admin']->id]);

            $stock = $this->inventoryFor($stable, $users['supervisor']);
            $horses = $this->horsesFor($stable, $users['admin']);
            $this->feedingsFor($horses, $stock, $users['supervisor']);
            $this->dailyTasksFor($horses, $users['supervisor']);
            $this->trainingsFor($horses, $users['trainer']);
            $this->healthRecordsFor($horses, $users['veterinarian']);
            $this->notesFor($horses, $users['supervisor']);
        });
    }

    private function usersFor(Stable $stable): array
    {
        $definitions = [
            'admin' => ['demo.admin@stablecare.local', 'Demo Administrators', 'admin'],
            'supervisor' => ['demo.supervisor@stablecare.local', 'Demo Uzraugs', 'supervisor'],
            'trainer' => ['demo.trainer@stablecare.local', 'Demo Treneris', 'trainer'],
            'veterinarian' => ['demo.vet@stablecare.local', 'Demo Veterinararsts', 'veterinarian'],
        ];

        $users = [];
        foreach ($definitions as $key => [$email, $name, $role]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'first_name' => Str::before($name, ' '),
                'last_name' => Str::after($name, ' '),
                'password' => self::PASSWORD,
                'role' => $role,
                'is_active' => true,
                'stable_id' => $stable->id,
            ]);

            if ((int) $user->stable_id !== (int) $stable->id || $user->role !== $role) {
                throw new RuntimeException("Demo account {$email} already exists outside its expected demo role or stable.");
            }

            if (!Hash::check(self::PASSWORD, $user->password)) {
                $user->update(['password' => self::PASSWORD, 'is_active' => true]);
            }
            $users[$key] = $user;
        }

        return $users;
    }

    private function inventoryFor(Stable $stable, User $creator): array
    {
        $definitions = [
            'auzas' => ['Auzas', 'Bariba', 100, 'kg', 25, 'Baribas noliktava'],
            'siens' => ['Siens', 'Rupja bariba', 500, 'kg', 120, 'Siena novietne'],
            'minerali' => ['Mineralu laizama', 'Papildbariba', 15, 'gab.', 4, 'Baribas noliktava'],
        ];

        $items = [];
        foreach ($definitions as $key => [$name, $category, $quantity, $unit, $minimum, $location]) {
            $item = InventoryItem::firstOrCreate(
                ['stable_id' => $stable->id, 'name' => $name],
                ['category' => $category, 'quantity' => $quantity, 'unit' => $unit, 'minimum_quantity' => $minimum, 'location' => $location, 'created_by' => $creator->id, 'notes' => 'Demonstracijas krājums.']
            );

            InventoryMovement::firstOrCreate(
                ['reference_key' => 'stablecare-demo-opening-' . $item->id],
                ['inventory_item_id' => $item->id, 'user_id' => $creator->id, 'quantity_delta' => $item->quantity, 'reason' => 'Demo stallja sakuma atlikums']
            );
            $items[$key] = $item;
        }

        return $items;
    }

    private function horsesFor(Stable $stable, User $creator): array
    {
        $definitions = [
            ['Mirdza', 'Latvijas siltasiņu', 'Ķēve', 'Bēra', 2016, 540],
            ['Rūdis', 'Hannoveras', 'Vērsis', 'Tumši bēra', 2018, 610],
            ['Zvaigzne', 'Latvijas siltasiņu', 'Ķēve', 'Pelēka', 2014, 525],
            ['Pērkons', 'Jaukta šķirne', 'Vērsis', 'Ruda', 2020, 480],
        ];

        $horses = [];
        foreach ($definitions as [$name, $breed, $sex, $colour, $birthYear, $weight]) {
            $horse = Horse::firstOrCreate(
                ['stable_id' => $stable->id, 'name' => $name],
                ['breed' => $breed, 'sex' => $sex, 'colour' => $colour, 'birth_date' => now()->setYear($birthYear)->startOfYear()->toDateString(), 'weight_kg' => $weight, 'status' => 'active', 'owner_name' => self::STABLE_NAME, 'notes' => 'Demonstracijas zirgs testa datiem.', 'created_by' => $creator->id]
            );
            $horses[] = $horse;
        }

        return $horses;
    }

    private function feedingsFor(array $horses, array $stock, User $creator): void
    {
        foreach ($horses as $horse) {
            foreach ([['07:00', $stock['siens'], 4, 'Rita siens'], ['08:00', $stock['auzas'], 2, 'Rita auzas'], ['13:00', $stock['auzas'], 2, 'Dienas auzas'], ['18:00', $stock['auzas'], 2, 'Vakara auzas'], ['19:00', $stock['siens'], 4, 'Vakara siens']] as [$time, $item, $amount, $name]) {
                $feeding = Feeding::firstOrCreate(
                    ['horse_id' => $horse->id, 'feed_time' => $time, 'inventory_item_id' => $item->id, 'start_date' => today()->toDateString()],
                    ['name' => $name, 'amount' => $amount, 'recurrence' => 'daily', 'weekdays' => [], 'active' => true, 'created_by' => $creator->id, 'notes' => 'Demonstracijas ikdienas plans.']
                );

                if (!$feeding->task()->exists()) {
                    Task::create([
                        'title' => 'Barosana: ' . $feeding->name,
                        'task_type' => 'feeding',
                        'horse_id' => $horse->id,
                        'feeding_id' => $feeding->id,
                        'start_date' => today()->toDateString(),
                        'task_time' => $time,
                        'priority' => 'normal',
                        'recurrence' => 'daily',
                        'weekdays' => [],
                        'inventory_item_id' => $item->id,
                        'consumption' => $amount,
                        'created_by' => $creator->id,
                        'active' => true,
                    ]);
                }
            }
        }
    }

    private function dailyTasksFor(array $horses, User $creator): void
    {
        foreach (array_slice($horses, 0, 3) as $index => $horse) {
            $title = ['Parbaudit udens spaini', 'Iztirit boksi', 'Izsukat un apskatit nagus'][$index];
            Task::firstOrCreate(
                ['title' => $title, 'horse_id' => $horse->id, 'created_by' => $creator->id, 'start_date' => today()->toDateString()],
                ['task_type' => $index === 1 ? 'cleaning' : 'care', 'assigned_to' => $creator->id, 'task_time' => ['06:30', '09:00', '10:00'][$index], 'priority' => 'normal', 'recurrence' => 'daily', 'weekdays' => [], 'active' => true, 'notes' => 'Demonstracijas ikdienas uzdevums.']
            );
        }
    }

    private function trainingsFor(array $horses, User $trainer): void
    {
        foreach (array_slice($horses, 0, 2) as $index => $horse) {
            Training::firstOrCreate(
                ['horse_id' => $horse->id, 'trainer_id' => $trainer->id, 'training_date' => today()->addDays($index + 1)->toDateString(), 'training_time' => ['10:00', '14:00'][$index]],
                ['training_type' => ['Iejāde', 'Kondīcijas treniņš'][$index], 'duration_minutes' => 45, 'status' => 'planned', 'notes' => 'Demonstracijas treniņš kalendāram.']
            );
        }
    }

    private function healthRecordsFor(array $horses, User $veterinarian): void
    {
        foreach (array_slice($horses, 0, 2) as $index => $horse) {
            HealthRecord::firstOrCreate(
                ['horse_id' => $horse->id, 'record_type' => 'Apskate', 'title' => 'Plansveida veselības parbaude', 'record_date' => today()->subDays(10)->toDateString()],
                ['veterinarian_id' => $veterinarian->id, 'diagnosis' => 'Vesels; novērošana turpinās.', 'notes' => 'Demonstracijas ieraksts. Nākamā vizīte ieplānota.', 'next_date' => today()->addDays(5 + $index)->toDateString()]
            );
        }
    }

    private function notesFor(array $horses, User $supervisor): void
    {
        foreach (array_slice($horses, 0, 2) as $horse) {
            HorseNote::firstOrCreate(
                ['horse_id' => $horse->id, 'user_id' => $supervisor->id, 'body' => 'Demonstracijas piezime: zirgs labi apeda rita baribu.'],
                []
            );
        }
    }
}
