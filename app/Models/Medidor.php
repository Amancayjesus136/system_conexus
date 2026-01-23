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
        'volt_l1_neutro',
        'volt_l2_neutro',
        'volt_l3_neutro',
        'volt_l1l2',
        'volt_l2l3',
        'volt_l3l1',
        'corr_l1',
        'corr_l2',
        'corr_l3',
        'pont_act_l1',
        'pont_act_l2',
        'pont_act_l3',
        'pont_act_total',
        'ener_act_total',
        'user_created',
        'user_updated',
        'estado_medidor',
    ];

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id_almacen');
    }
}
