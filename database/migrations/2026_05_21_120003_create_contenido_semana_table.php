<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenido pedagógico de UNA semana de UN curso en UNA sección.
 * El docente asignado al seccion_curso es quien lo edita.
 * 1:1 con la combinación (semana, seccion_curso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenido_semana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semana_id')
                ->constrained('semanas')
                ->cascadeOnDelete();
            $table->foreignId('seccion_curso_id')
                ->constrained('seccion_curso')
                ->cascadeOnDelete();
            $table->string('titulo', 150)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('recursos_url')->nullable(); // links separados por línea
            $table->text('tarea')->nullable();
            $table->timestamps();

            $table->unique(['semana_id', 'seccion_curso_id'], 'cs_unique');
            $table->index('seccion_curso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenido_semana');
    }
};
