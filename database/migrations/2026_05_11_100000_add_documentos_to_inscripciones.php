<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas para subir documentos durante la inscripción pública:
 *  - comprobante_pago_url: voucher del pago de matrícula (Yape, transferencia)
 *  - certificado_estudios_url: certificado del año anterior del alumno
 *
 * (Migración hecha por Persona C para desbloquear el flujo end-to-end;
 *  cuando Persona B reorganice su módulo, puede consolidarse.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('comprobante_pago_url', 255)->nullable()->after('comprobante_url');
            $table->string('certificado_estudios_url', 255)->nullable()->after('comprobante_pago_url');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['comprobante_pago_url', 'certificado_estudios_url']);
        });
    }
};
