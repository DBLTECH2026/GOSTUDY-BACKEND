<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnDelete();
            $table->string('nombre', 100); // Matemática, Comunicación...
            $table->string('codigo', 20)->unique();
            $table->unsignedTinyInteger('horas_semana')->default(4);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('grado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
