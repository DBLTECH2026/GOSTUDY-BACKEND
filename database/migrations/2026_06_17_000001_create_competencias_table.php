<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('competencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index('curso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencias');
    }
};
