<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('purchase_reference');
        });

        Schema::table('auto_rules', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('comment');
        });

        Schema::table('auto_rules', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
