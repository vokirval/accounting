<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('requisites_file_url')->nullable()->after('requisites');
            $table->timestamp('requisites_file_uploaded_at')->nullable()->after('requisites_file_url');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['requisites_file_url', 'requisites_file_uploaded_at']);
        });
    }
};
