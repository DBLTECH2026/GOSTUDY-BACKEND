<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bimestre = unidad pedagógica grande dentro de un periodo académico.
 * Típicamente 4 por año (I, II, III, IV) en colegios peruanos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')
                ->constrained('periodos_academicos')
                ->cascadeOnDelete();
            $table->string('nombre', 30); // 'Bimestre I'
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedTinyInteger('orden'); // 1-4
            $table->timestamps();

            $table->unique(['periodo_id', 'orden']);
            $table->index('periodo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimestres');
    }
};
