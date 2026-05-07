<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->year('anio');
            $table->string('descripcion', 100); // "2026 — I"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['planificado', 'activo', 'cerrado'])->default('planificado');
            $table->timestamps();

            $table->index(['anio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
