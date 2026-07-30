<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recognition_records', function (Blueprint $table) {
            $table->decimal('location_accuracy_meters', 8, 2)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('recognition_records', function (Blueprint $table) {
            $table->dropColumn('location_accuracy_meters');
        });
    }
};
