<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_rule_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_rule_id')->constrained('auto_rules')->cascadeOnDelete();
            $table->string('level');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_rule_logs');
    }
};
