<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Almacen extends Model
{
    protected $table = 'almacenes';
    protected $primaryKey = 'id_almacen';

    protected $fillable = [
        'nombre_almacen',
        'id_departamento',
        'ip',
        'port',
        'user_created',
        'user_updated',
        'estado_almacen',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento', 'id_departamento');
    }

    public function medidores()
    {
        return $this->hasMany(Medidor::class, 'id_almacen', 'id_almacen');
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
