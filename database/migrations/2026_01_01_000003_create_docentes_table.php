<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();
            $table->string('codigo_docente', 20)->unique();
            $table->string('especialidad', 100)->nullable();
            $table->string('grado_academico', 100)->nullable();
            $table->timestamps();

            $table->index('codigo_docente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
