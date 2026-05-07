<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_familiares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('estudiantes')
                ->cascadeOnDelete();
            $table->enum('tipo', ['padre', 'madre', 'apoderado']);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('dni', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('ocupacion', 100)->nullable();
            $table->string('parentesco', 50)->nullable();
            $table->boolean('vive_con')->default(true);
            $table->boolean('es_titular')->default(false);
            $table->timestamps();

            $table->index(['estudiante_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_familiares');
    }
};
