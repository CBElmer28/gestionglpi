<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('glpi_id')->nullable();
            $table->string('title');
            $table->string('author');
            $table->string('isbn', 50)->unique();
            $table->string('edition', 100)->nullable();
            $table->string('genre', 100)->nullable();
            $table->string('publisher', 100)->nullable();
            $table->string('status', 50)->nullable()->default('Disponible');
            $table->text('synopsis')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
