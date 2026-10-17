<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('return_front_photo', 500)->nullable();
            $table->string('return_back_photo', 500)->nullable();
            $table->string('return_device', 255)->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->string('photo_back_path', 500)->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['return_front_photo', 'return_back_photo', 'return_device', 'returned_by']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn('photo_back_path');
        });
    }
};
