<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->string('user_name');
            $table->date('loan_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['Activo', 'Devuelto', 'Atrasado'])->default('Activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
