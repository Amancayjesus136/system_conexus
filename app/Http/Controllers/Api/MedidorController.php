<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Departamento;
use App\Models\Medidor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedidorController extends Controller
{
    public function store_django(Request $request)
    {
        DB::beginTransaction();

        try {
            $departamento = Departamento::find($request->id_departamento);
            $nombre_departamento = $departamento ? $departamento->nombre_departamento : 'Desconocido';

            $almacen = Almacen::where('ip', $request->ip)
                ->where('port', $request->port)
                ->where('id_departamento', $request->id_departamento)
                ->first();

            if (!$almacen) {
                $almacen = Almacen::create([
                    'nombre_almacen'   => 'Almacen desde API',
                    'id_departamento'  => $request->id_departamento,
                    'ip'               => $request->ip,
                    'port'             => $request->port,
                    'user_created'     => $request->user_created,
                    'user_updated'     => $request->user_updated,
                    'estado_almacen'   => 1,
                    'timestamp'        => now(),
                ]);
            }

            foreach ($request->lecturas as $cod_medidor => $lectura) {

                // SIEMPRE se registra un nuevo registro (histórico)
                Medidor::create([
                    'cod_medidor'    => $cod_medidor,
                    'id_almacen'     => $almacen->id_almacen,
                    'eac_Tar_1'      => $lectura['eac_Tar_1'] ?? 0,
                    'eac_Tar_2'      => $lectura['eac_Tar_2'] ?? 0,
                    'eac_Total'      => $lectura['eac_Total'] ?? 0,
                    'Max_demanda'    => $lectura['Max_demanda'] ?? 0,
                    'eric_Total'     => $lectura['eric_Total'] ?? 0,
                    'estado_medidor' => 1,
                    'user_created'   => $request->user_created,
                    'user_updated'   => $request->user_updated,
                ]);
            }

            DB::commit();

            return response()->json([
                'message'       => 'Lecturas registradas correctamente (histórico)',
                'almacen_ip'    => $almacen->ip,
                'departamento'  => $nombre_departamento,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
