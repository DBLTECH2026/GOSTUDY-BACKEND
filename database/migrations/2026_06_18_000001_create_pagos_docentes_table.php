<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos_docentes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $t->foreignId('periodo_id')->nullable()->constrained('periodos_academicos')->nullOnDelete();
            $t->enum('concepto', ['sueldo', 'bono', 'otros'])->default('sueldo');
            $t->string('descripcion', 150);
            $t->decimal('monto', 10, 2);
            $t->unsignedTinyInteger('mes')->nullable();
            $t->year('anio');
            $t->date('fecha_pago')->nullable();
            $t->enum('metodo', ['efectivo', 'transferencia', 'yape', 'plin', 'otro'])->nullable();
            $t->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $t->text('observaciones')->nullable();
            $t->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->timestamps();
            $t->index(['docente_id', 'estado']);
            $t->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_docentes');
    }
};
