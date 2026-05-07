<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estudiantes — auth con DNI + PIN 6 dígitos (NO usuario_id).
 * El padre elige el PIN al inscribir; se almacena hasheado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_estudiante', 20)->unique();
            $table->string('dni', 15)->unique();
            $table->string('pin'); // Hash::make($pin6digitos)
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('direccion', 200);
            $table->string('departamento', 60)->nullable();
            $table->string('provincia', 60)->nullable();
            $table->string('distrito', 60)->nullable();
            $table->string('ie_procedencia', 150)->nullable();
            $table->year('anio_procedencia')->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->enum('estado', ['activo', 'retirado', 'egresado'])->default('activo');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('dni');
            $table->index('codigo_estudiante');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
