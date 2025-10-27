<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_goals', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_goals', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->after('user_id');
            }
            if (!Schema::hasColumn('financial_goals', 'priority')) {
                // use string instead of enum for easier evolution; controller validates values
                $table->string('priority', 16)->default('medium')->after('target_date');
            }
            if (!Schema::hasColumn('financial_goals', 'notes')) {
                $table->text('notes')->nullable()->after('monthly_contribution');
            }
        });

        // Normalize enums created previously with different naming
        // Safely attempt ALTERs only on MySQL-compatible drivers
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            try {
                DB::statement("ALTER TABLE `financial_goals` MODIFY `status` ENUM('active','paused','completed','canceled') NOT NULL DEFAULT 'active'");
            } catch (\Throwable $e) {
                // ignore if not supported
            }
            try {
                DB::statement("ALTER TABLE `financial_goals` MODIFY `goal_type` ENUM('savings','investment','purchase','debt_payment','emergency_fund') NOT NULL");
            } catch (\Throwable $e) {
                // ignore if not supported
            }
        }
    }

    public function down(): void
    {
        // Revert best-effort: drop added columns if exist; don't revert enum changes
        Schema::table('financial_goals', function (Blueprint $table) {
            if (Schema::hasColumn('financial_goals', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('financial_goals', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('financial_goals', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });
    }
};
