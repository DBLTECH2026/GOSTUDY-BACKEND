<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GOSTUDY — Persona C · Pagos.
 *
 * Cada matrícula aprobada genera 11 filas en esta tabla:
 * 1 con concepto 'matricula' (vence inmediato) + 10 con concepto 'pension'
 * (mes 3 a 12, vencimiento día 5 de cada mes).
 *
 * @property int $id
 * @property int $matricula_id
 * @property string $concepto
 * @property string $descripcion
 * @property string $monto
 * @property int|null $mes
 * @property \Illuminate\Support\Carbon $fecha_vencimiento
 * @property \Illuminate\Support\Carbon|null $fecha_pago
 * @property string|null $metodo
 * @property string $estado
 * @property string|null $comprobante_url
 * @property string|null $observaciones
 * @property int|null $registrado_por
 */
class Pago extends Model
{
    public const CONCEPTO_MATRICULA = 'matricula';
    public const CONCEPTO_PENSION   = 'pension';
    public const CONCEPTO_OTROS     = 'otros';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGADO    = 'pagado';
    public const ESTADO_VENCIDO   = 'vencido';
    public const ESTADO_ANULADO   = 'anulado';

    public const METODO_EFECTIVO      = 'efectivo';
    public const METODO_TRANSFERENCIA = 'transferencia';
    public const METODO_YAPE          = 'yape';
    public const METODO_PLIN          = 'plin';
    public const METODO_OTRO          = 'otro';

    protected $table = 'pagos';

    protected $fillable = [
        'matricula_id',
        'concepto',
        'descripcion',
        'monto',
        'mes',
        'fecha_vencimiento',
        'fecha_pago',
        'metodo',
        'estado',
        'comprobante_url',
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto'             => 'decimal:2',
            'mes'               => 'integer',
            'fecha_vencimiento' => 'date',
            'fecha_pago'        => 'date',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopePagados(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_PAGADO);
    }

    public function scopeVencidos(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_VENCIDO);
    }

    public function scopePorMatricula(Builder $q, int $matriculaId): Builder
    {
        return $q->where('matricula_id', $matriculaId);
    }

    public function scopePorMes(Builder $q, int $mes): Builder
    {
        return $q->where('mes', $mes);
    }

    public function estaPendienteOVencido(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_VENCIDO], true);
    }
}
