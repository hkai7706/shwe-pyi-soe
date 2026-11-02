<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['students', 'teachers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->longText('photo_data')->nullable()->after('photo');
                $table->string('photo_mime', 100)->nullable()->after('photo_data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['students', 'teachers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['photo_data', 'photo_mime']);
            });
        }
    }
};
