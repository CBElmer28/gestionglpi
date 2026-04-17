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
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('genre_id')->nullable()->after('isbn');
            $table->unsignedBigInteger('publisher_id')->nullable()->after('genre_id');
            
            $table->foreign('genre_id')->references('id')->on('genres')->nullOnDelete();
            $table->foreign('publisher_id')->references('id')->on('publishers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['genre_id']);
            $table->dropForeign(['publisher_id']);
            $table->dropColumn(['genre_id', 'publisher_id']);
        });
    }
};
