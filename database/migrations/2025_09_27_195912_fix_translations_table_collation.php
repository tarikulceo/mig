<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix collation mismatch by converting the translations table and columns to utf8mb4
        if (Schema::hasTable('translations')) {
            DB::statement('ALTER TABLE translations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang_key TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang_value TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }

        // Also fix app_translations table if it exists
        if (Schema::hasTable('app_translations')) {
            DB::statement('ALTER TABLE app_translations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang_key VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang_value VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to utf8 collation for translations table
        if (Schema::hasTable('translations')) {
            DB::statement('ALTER TABLE translations CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang_key TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE translations MODIFY lang_value TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        }

        // Revert back to utf8 collation for app_translations table
        if (Schema::hasTable('app_translations')) {
            DB::statement('ALTER TABLE app_translations CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang_key VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            DB::statement('ALTER TABLE app_translations MODIFY lang_value VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        }
    }
};
