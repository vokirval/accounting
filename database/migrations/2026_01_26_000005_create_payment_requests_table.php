<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->text('requisites');
            $table->decimal('amount', 12, 2);
            $table->string('purchase_reference')->nullable();
            $table->boolean('ready_for_payment')->default(false)->index();
            $table->boolean('paid')->default(false)->index();
            $table->foreignId('paid_account_id')->nullable()->constrained('payment_accounts')->restrictOnDelete();
            $table->string('receipt_url')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
