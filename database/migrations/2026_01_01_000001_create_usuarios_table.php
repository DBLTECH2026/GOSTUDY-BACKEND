<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GOSTUDY — Persona A · Auth + Personas
 * Tabla base de admin/docente. Estudiantes va aparte (auth con DNI+PIN).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('dni', 15)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->enum('rol', ['admin', 'docente']);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('rol');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
