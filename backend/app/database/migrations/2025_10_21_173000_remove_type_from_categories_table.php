<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('categories', 'type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('categories', 'type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->enum('type', ['income', 'expense'])->nullable();
                $table->index('type');
            });
        }
    }
};
