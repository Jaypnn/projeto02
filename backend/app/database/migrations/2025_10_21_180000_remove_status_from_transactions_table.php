<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'status')) {
            Schema::table('transactions', function (Blueprint $table) {
                // drop index if exists (Laravel won't error if it doesn't match)
                try { $table->dropIndex(['status', 'transaction_date']); } catch (\Throwable $e) {}
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed')->after('transaction_date');
                $table->index(['status', 'transaction_date']);
            });
        }
    }
};
