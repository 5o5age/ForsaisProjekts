<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120)->default('Barošana');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('amount', 10, 3);
            $table->time('feed_time')->default('08:00:00');
            $table->string('recurrence', 24)->default('daily');
            $table->json('weekdays')->nullable();
            $table->string('month_ranges', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('task_type', 40)->default('other')->index();
            $table->foreignId('horse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('feeding_id')->nullable()->unique()->constrained('feedings')->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->time('task_time')->nullable();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('recurrence', 24)->default('once');
            $table->json('weekdays')->nullable();
            $table->string('month_ranges', 100)->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('consumption', 10, 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->unique(['task_id', 'occurrence_date']);
            $table->index('occurrence_date');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity_delta', 12, 3);
            $table->string('reason', 100);
            $table->string('reference_key', 180)->nullable()->unique();
            $table->timestamps();
            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('task_completions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('feedings');
    }
};
