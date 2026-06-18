<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDocente extends Model
{
    public const CONCEPTOS = ['sueldo', 'bono', 'otros'];
    public const ESTADOS = ['pendiente', 'pagado', 'anulado'];

    protected $table = 'pagos_docentes';

    protected $fillable = [
        'docente_id', 'periodo_id', 'concepto', 'descripcion', 'monto',
        'mes', 'anio', 'fecha_pago', 'metodo', 'estado', 'observaciones', 'registrado_por',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
