<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot — qué cursos dicta cada sección + qué docente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seccion_curso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')
                ->constrained('secciones')
                ->cascadeOnDelete();
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnDelete();
            $table->foreignId('docente_id')
                ->nullable()
                ->constrained('docentes')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['seccion_id', 'curso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seccion_curso');
    }
};
