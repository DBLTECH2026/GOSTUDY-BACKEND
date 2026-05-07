<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnDelete();
            $table->foreignId('periodo_id')
                ->constrained('periodos_academicos')
                ->cascadeOnDelete();
            $table->foreignId('docente_tutor_id')
                ->nullable()
                ->constrained('docentes')
                ->nullOnDelete();
            $table->string('nombre', 5); // A, B, C...
            $table->unsignedSmallInteger('capacidad')->default(30);
            $table->timestamps();

            $table->unique(['grado_id', 'periodo_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
