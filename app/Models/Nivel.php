<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nivel extends Model
{
    protected $table = 'niveles';

    protected $fillable = ['nombre', 'orden'];

    public function grados(): HasMany
    {
        return $this->hasMany(Grado::class)->orderBy('orden');
    }
}
