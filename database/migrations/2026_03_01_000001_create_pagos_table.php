<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GOSTUDY — Persona C · Pagos
 * Cuotas: 1 matrícula + 10 pensiones (mes 3 a 12).
 * Se generan automáticamente cuando admin aprueba una matrícula
 * (vía evento MatriculaAprobada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();
            $table->enum('concepto', ['matricula', 'pension', 'otros']);
            $table->string('descripcion', 150);
            $table->decimal('monto', 10, 2);
            $table->unsignedTinyInteger('mes')->nullable(); // 3 a 12 (solo pensión)
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->enum('metodo', ['efectivo', 'transferencia', 'yape', 'plin', 'otro'])->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'anulado'])->default('pendiente');
            $table->string('comprobante_url', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['matricula_id', 'estado']);
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index(['concepto', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
