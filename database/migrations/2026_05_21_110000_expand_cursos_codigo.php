<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía 'codigo' de cursos a 40 chars para acomodar el sufijo "__del_{id}"
 * que se añade automáticamente al hacer soft delete (libera el slug activo
 * sin romper el índice UNIQUE de MySQL que ignora el soft delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('codigo', 40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('codigo', 20)->change();
        });
    }
};
