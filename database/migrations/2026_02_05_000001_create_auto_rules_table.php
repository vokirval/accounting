<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('expense_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->text('requisites')->nullable();
            $table->string('requisites_file_url')->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('ready_for_payment')->default(false);
            $table->string('frequency');
            $table->unsignedInteger('interval_days')->nullable();
            $table->json('days_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->date('start_date');
            $table->time('run_at');
            $table->string('timezone')->default('Europe/Kyiv');
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_rules');
    }
};
