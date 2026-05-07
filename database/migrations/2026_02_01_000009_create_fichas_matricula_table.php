<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha — datos administrativos de la matrícula (apoderado titular, documentos, etc.)
 * 1:1 con matriculas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_matricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')
                ->unique()
                ->constrained('matriculas')
                ->cascadeOnDelete();
            $table->foreignId('apoderado_principal_id')
                ->nullable()
                ->constrained('perfiles_familiares')
                ->nullOnDelete();
            $table->string('documento_partida_url', 255)->nullable();
            $table->string('documento_dni_url', 255)->nullable();
            $table->string('documento_certificado_estudios_url', 255)->nullable();
            $table->text('observaciones_administrativas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas_matricula');
    }
};
