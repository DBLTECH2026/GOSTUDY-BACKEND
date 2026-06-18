<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('seccion_curso_id')->constrained('seccion_curso')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('estado', ['presente', 'tarde', 'falta', 'justificada']);
            $table->timestamps();
            $table->unique(['matricula_id', 'seccion_curso_id', 'fecha'], 'asist_unica');
            $table->index(['seccion_curso_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
