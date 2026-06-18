<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('seccion_curso_id')->constrained('seccion_curso')->cascadeOnDelete();
            $table->foreignId('competencia_id')->constrained('competencias')->cascadeOnDelete();
            $table->foreignId('bimestre_id')->constrained('bimestres')->cascadeOnDelete();
            $table->enum('nota', ['AD', 'A', 'B', 'C'])->nullable();
            $table->text('conclusion_descriptiva')->nullable();
            $table->timestamps();
            $table->unique(['matricula_id', 'competencia_id', 'bimestre_id'], 'calif_unica');
            $table->index(['seccion_curso_id', 'bimestre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
