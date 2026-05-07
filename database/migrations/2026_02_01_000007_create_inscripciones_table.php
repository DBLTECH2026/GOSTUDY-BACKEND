<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscripción pública — el padre llena el form (sin auth).
 * Todavía NO crea estudiante; eso ocurre cuando admin aprueba.
 * Datos del padre/madre/apoderado se guardan como JSON para flexibilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_inscripcion', 30)->unique(); // INS-2026-00001

            $table->foreignId('periodo_id')
                ->constrained('periodos_academicos')
                ->cascadeOnDelete();
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnDelete();
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnDelete();
            $table->foreignId('seccion_sugerida_id')
                ->nullable()
                ->constrained('secciones')
                ->nullOnDelete();

            // Datos del estudiante
            $table->string('dni_estudiante', 15);
            $table->string('nombres_estudiante', 100);
            $table->string('apellidos_estudiante', 100);
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('direccion', 200);
            $table->string('departamento', 60)->nullable();
            $table->string('provincia', 60)->nullable();
            $table->string('distrito', 60)->nullable();
            $table->string('ie_procedencia', 150)->nullable();
            $table->year('anio_procedencia')->nullable();
            $table->string('pin_hash'); // PIN elegido por el padre

            // Datos familiares (JSON flexible)
            $table->json('datos_familiares')->nullable();

            // Estado y auditoría
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->string('comprobante_url', 255)->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->timestamp('fecha_inscripcion')->useCurrent();
            $table->foreignId('aprobada_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamp('aprobada_en')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('dni_estudiante');
            $table->index(['periodo_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
