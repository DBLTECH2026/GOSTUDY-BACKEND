<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grado extends Model
{
    protected $table = 'grados';

    protected $fillable = ['nivel_id', 'nombre', 'orden'];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }
}
