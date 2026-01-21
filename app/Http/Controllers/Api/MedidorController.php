<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Departamento;
use App\Models\Medidor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                    'nombre_almacen' => 'Almacen desde API',
                    'id_departamento' => $request->id_departamento,
                    'ip' => $request->ip,
                    'port' => $request->port,
                    'user_created' => $request->user_created,
                    'user_updated' => $request->user_updated,
                    'estado_almacen' => 1,
                    'timestamp' => now(),
                ]);

                // ActivityLog::create([
                //     'action' => 'crear',
                //     'description' => ($user = Auth::user())
                //         ? $user->name . ' creó un nuevo almacén con IP: ' . $request->ip . ' en el departamento ' . $nombre_departamento . ' desde la plataforma local.'
                //         : 'Un proceso externo creó un nuevo almacén con IP: ' . $request->ip . ' en el departamento ' . $nombre_departamento . ' desde la plataforma local.',
                //     'user_id' => Auth::id() ?? $request->user_created ?? null,
                //     'table' => 'Local',
                // ]);
            }

            foreach ($request->lecturas as $cod_medidor => $lectura) {
                $ultimoMedidor = Medidor::where('id_almacen', $almacen->id_almacen)
                    ->where('cod_medidor', $cod_medidor)
                    ->latest('created_at')
                    ->first();

                if ($ultimoMedidor) {
                    if ($ultimoMedidor->created_at->isToday()) {
                        $cambio = (
                            $ultimoMedidor->eac_Tar_1 != ($lectura['eac_Tar_1'] ?? 0) ||
                            $ultimoMedidor->eac_Tar_2 != ($lectura['eac_Tar_2'] ?? 0) ||
                            $ultimoMedidor->eac_Total != ($lectura['eac_Total'] ?? 0) ||
                            $ultimoMedidor->Max_demanda != ($lectura['Max_demanda'] ?? 0) ||
                            $ultimoMedidor->eric_Total != ($lectura['eric_Total'] ?? 0)
                        );

                        if ($cambio) {
                            $ultimoMedidor->update([
                                'eac_Tar_1'    => $lectura['eac_Tar_1'] ?? 0,
                                'eac_Tar_2'    => $lectura['eac_Tar_2'] ?? 0,
                                'eac_Total'    => $lectura['eac_Total'] ?? 0,
                                'Max_demanda'  => $lectura['Max_demanda'] ?? 0,
                                'eric_Total'   => $lectura['eric_Total'] ?? 0,
                                'user_updated' => $request->user_updated,
                            ]);

                            // ActivityLog::create([
                            //     'action' => 'actualizar',
                            //     'description' => 'El medidor ' . $cod_medidor . ' fue actualizado en el almacén con IP: ' . $request->ip . ' (' . $nombre_departamento . ') desde la plataforma local.',
                            //     'user_id' => Auth::id() ?? $request->user_updated ?? null,
                            //]);
                        }
                    } else {
                        Medidor::create([
                            'cod_medidor'  => $cod_medidor,
                            'id_almacen'   => $almacen->id_almacen,
                            'eac_Tar_1'    => $lectura['eac_Tar_1'] ?? 0,
                            'eac_Tar_2'    => $lectura['eac_Tar_2'] ?? 0,
                            'eac_Total'    => $lectura['eac_Total'] ?? 0,
                            'Max_demanda'  => $lectura['Max_demanda'] ?? 0,
                            'eric_Total'   => $lectura['eric_Total'] ?? 0,
                            'estado_medidor'       => 1,
                            'user_created' => $request->user_created,
                            'user_updated' => $request->user_updated,
                        ]);

                        // ActivityLog::create([
                        //     'action' => 'crear',
                        //     'description' => 'Se creó un nuevo medidor ' . $cod_medidor . ' en el almacén con IP: ' . $request->ip . ' (' . $nombre_departamento . ') desde la plataforma local.',
                        //     'user_id' => Auth::id() ?? $request->user_created ?? null,
                        // ]);
                    }
                } else {
                    Medidor::create([
                        'cod_medidor'  => $cod_medidor,
                        'id_almacen'   => $almacen->id_almacen,
                        'eac_Tar_1'    => $lectura['eac_Tar_1'] ?? 0,
                        'eac_Tar_2'    => $lectura['eac_Tar_2'] ?? 0,
                        'eac_Total'    => $lectura['eac_Total'] ?? 0,
                        'Max_demanda'  => $lectura['Max_demanda'] ?? 0,
                        'eric_Total'   => $lectura['eric_Total'] ?? 0,
                        'estado_medidor'       => 1,
                        'user_created' => $request->user_created,
                        'user_updated' => $request->user_updated,
                    ]);

                    // ActivityLog::create([
                    //     'action' => 'crear',
                    //     'description' => 'Se registró un nuevo medidor ' . $cod_medidor . ' en el almacén con IP: ' . $request->ip . ' (' . $nombre_departamento . ') desde la plataforma local.',
                    //     'user_id' => Auth::id() ?? $request->user_created ?? null,
                    //     'table' => 'Local',
                    // ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => $almacen->wasRecentlyCreated
                    ? 'Almacén y medidores registrados correctamente'
                    : 'Medidores procesados correctamente',
                'almacen_ip' => $almacen->ip,
                'departamento' => $nombre_departamento,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
