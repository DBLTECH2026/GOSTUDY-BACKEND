<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materiales (archivos) subidos por el docente para una semana de su curso.
 * Independiente de contenido_semana: el docente puede subir PDFs/imágenes
 * sin haber escrito título/descripción todavía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_semana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semana_id')
                ->constrained('semanas')
                ->cascadeOnDelete();
            $table->foreignId('seccion_curso_id')
                ->constrained('seccion_curso')
                ->cascadeOnDelete();
            $table->string('nombre_original', 200);
            $table->string('ruta', 255); // path relativo dentro del disco 'public'
            $table->string('tipo', 100)->nullable(); // mime
            $table->unsignedInteger('tamano')->default(0); // bytes
            $table->foreignId('subido_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['semana_id', 'seccion_curso_id'], 'mat_semana_sc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_semana');
    }
};
