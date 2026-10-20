<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_type', 60)->default('Apskate')->index();
            $table->date('record_date')->index();
            $table->string('title', 180);
            $table->string('diagnosis', 180)->nullable();
            $table->text('notes')->nullable();
            $table->date('next_date')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('training_date')->index();
            $table->time('training_time')->nullable();
            $table->string('training_type', 100);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status', 24)->default('planned')->index();
            $table->text('notes')->nullable();
            $table->text('results')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('health_records');
    }
};
