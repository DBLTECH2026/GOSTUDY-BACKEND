<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semana = sub-unidad dentro de un bimestre. Típicamente 8-10 por bimestre.
 * Las fechas se generan automáticamente desde fecha_inicio del bimestre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bimestre_id')
                ->constrained('bimestres')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('numero'); // 1, 2, 3... dentro del bimestre
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();

            $table->unique(['bimestre_id', 'numero']);
            $table->index('bimestre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semanas');
    }
};
