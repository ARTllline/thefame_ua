<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'code')) {
            return;
        }

        if ($this->indexExists('products', 'products_code_unique')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique('products_code_unique');
            });
        }

        if (! $this->indexExists('products', 'products_code_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'code')) {
            return;
        }

        if ($this->indexExists('products', 'products_code_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_code_index');
            });
        }

        $hasDuplicateCodes = DB::table('products')
            ->whereNotNull('code')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateCodes && ! $this->indexExists('products', 'products_code_unique')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unique('code');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->count() > 0;
    }
};
