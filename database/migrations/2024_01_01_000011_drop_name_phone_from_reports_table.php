<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reports', 'name') || Schema::hasColumn('reports', 'phone')) {
            Schema::table('reports', function (Blueprint $table) {
                if (Schema::hasColumn('reports', 'name')) {
                    $table->dropColumn('name');
                }
                if (Schema::hasColumn('reports', 'phone')) {
                    $table->dropColumn('phone');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('phone', 20)->nullable()->after('name');
        });
    }
};
