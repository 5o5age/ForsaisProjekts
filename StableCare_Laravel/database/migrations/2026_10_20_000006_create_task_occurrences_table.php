<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('active')->default(true)->index();
        });
        Schema::create('task_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('original_date');
            $table->date('scheduled_date');
            $table->string('scope', 12)->default('single');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'original_date']);
            $table->index(['task_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_occurrences');
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
