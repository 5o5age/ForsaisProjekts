<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('horses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->date('birth_date')->nullable();
            $table->string('breed', 50)->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('colour', 80)->nullable();
            $table->string('chip_number', 80)->nullable()->unique();
            $table->decimal('weight_kg', 7, 2)->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);
            $table->string('category', 80)->default('Barība');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('unit', 20)->default('kg');
            $table->decimal('minimum_quantity', 12, 3)->default(0);
            $table->string('location', 140)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('horses');
    }
};
