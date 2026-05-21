<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horario detallado: cada fila es UN slot (un día + hora inicio + hora fin)
 * para una asignación seccion_curso. Un curso puede tener varios horarios
 * (ej. Matemática Lun/Mié/Vie 8-9:30 = 3 filas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_curso_id')
                ->constrained('seccion_curso')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1=Lun, 2=Mar, ..., 7=Dom
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 50)->nullable();
            $table->timestamps();

            $table->index(['seccion_curso_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
