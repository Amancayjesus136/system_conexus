<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Departamento extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'id_departamento';

    protected $fillable = [
        'nombre_departamento',
        'user_created',
        'user_updated',
        'estado_departamento',
    ];

    public function almacenes()
    {
        return $this->hasMany(Almacen::class, 'id_departamento', 'id_departamento');
    }

    public function getCreatedAgoAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getUpdatedAgoAttribute()
    {
        return Carbon::parse($this->updated_at)->diffForHumans();
    }
}
