<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competencia extends Model
{
    use SoftDeletes;

    protected $table = 'competencias';

    protected $fillable = ['curso_id', 'nombre', 'descripcion', 'orden'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
