<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnDelete();
            $table->string('nombre', 30); // 1ro, 2do, 5 años...
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();

            $table->unique(['nivel_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
