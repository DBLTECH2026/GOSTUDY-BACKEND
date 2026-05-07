<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrícula activa — se crea cuando admin aprueba la inscripción.
 * Un estudiante NO puede tener 2 matrículas en el mismo periodo (UNIQUE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('estudiantes')
                ->cascadeOnDelete();
            $table->foreignId('periodo_id')
                ->constrained('periodos_academicos')
                ->cascadeOnDelete();
            $table->foreignId('seccion_id')
                ->constrained('secciones')
                ->cascadeOnDelete();
            $table->foreignId('inscripcion_id')
                ->nullable()
                ->constrained('inscripciones')
                ->nullOnDelete();
            $table->date('fecha_matricula');
            $table->enum('estado', ['activa', 'retirada', 'egresada'])->default('activa');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['estudiante_id', 'periodo_id']);
            $table->index(['periodo_id', 'estado']);
            $table->index('seccion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
