<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medidor extends Model
{
    protected $table = 'medidores';
    protected $primaryKey = 'id_medidor';

    protected $fillable = [
        'cod_medidor',
        'id_almacen',
        'eac_Tar_1',
        'eac_Tar_2',
        'eac_Total',
        'Max_demanda',
        'eric_Total',
        'user_created',
        'user_updated',
        'estado_medidor',
    ];

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id_almacen');
    }
}
