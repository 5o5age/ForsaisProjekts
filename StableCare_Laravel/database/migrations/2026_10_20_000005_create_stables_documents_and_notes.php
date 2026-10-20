<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('address', 150)->nullable();
            $table->string('subscription_type', 30)->default('free');
            $table->string('invite_code', 64)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 30)->nullable()->after('name');
            $table->string('last_name', 30)->nullable()->after('first_name');
            $table->foreignId('stable_id')->nullable()->after('id')->constrained('stables')->nullOnDelete();
        });
        Schema::table('horses', function (Blueprint $table) {
            $table->foreignId('stable_id')->nullable()->after('id')->constrained('stables')->nullOnDelete();
            $table->string('owner_name', 100)->nullable()->after('chip_number');
        });
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('stable_id')->nullable()->after('id')->constrained('stables')->nullOnDelete();
            $table->date('expires_at')->nullable()->after('location');
            $table->unique(['stable_id', 'name']);
        });

        Schema::create('horse_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->string('document_type', 50);
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
            $table->index(['horse_id', 'document_type']);
        });

        Schema::create('horse_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('body', 500);
            $table->timestamps();
            $table->index(['horse_id', 'created_at']);
        });

        Schema::table('task_completions', function (Blueprint $table) {
            $table->string('notes', 500)->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
        Schema::dropIfExists('horse_notes');
        Schema::dropIfExists('horse_documents');
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['stable_id', 'name']);
            $table->dropColumn('expires_at');
            $table->dropConstrainedForeignId('stable_id');
        });
        Schema::table('horses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stable_id');
            $table->dropColumn('owner_name');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stable_id');
            $table->dropColumn(['first_name', 'last_name']);
        });
        Schema::dropIfExists('stables');
    }
};
