<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abouts') && ! Schema::hasColumn('abouts', 'label_ua')) {
            Schema::table('abouts', function (Blueprint $table) {
                $table->json('label_ua')->nullable();
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'seo_text')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->json('seo_text')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('abouts') && Schema::hasColumn('abouts', 'label_ua')) {
            Schema::table('abouts', function (Blueprint $table) {
                $table->dropColumn('label_ua');
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'seo_text')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('seo_text');
            });
        }
    }
};
