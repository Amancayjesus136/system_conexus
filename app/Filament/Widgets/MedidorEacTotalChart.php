<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedidorEacTotalChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    // Propiedades
    public ?int $medidorId = null;
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $periodo = 'diario';
    public array $campos = [];

    protected array $colores = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1',
    ];

    protected function getPollingInterval(): ?string
    {
        if (in_array($this->periodo, ['1min', '5min'])) {
            return '5s';
        }
        return null;
    }

    public function getHeading(): string
    {
        if (empty($this->campos)) return 'Seleccione parámetros';
        $nombres = array_map(fn($c) => ucfirst(str_replace('_', ' ', $c)), $this->campos);
        return 'Histórico (' . ucfirst($this->periodo) . '): ' . implode(', ', $nombres);
    }

    protected function getData(): array
    {
        if (! $this->medidorId || empty($this->campos)) {
            return ['labels' => [], 'datasets' => []];
        }

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        $query = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen);

        if ($this->fechaInicio) {
            $inicioLimpio = Carbon::parse($this->fechaInicio)->format('Y-m-d');
            $query->where('created_at', '>=', $inicioLimpio . ' 00:00:00');
        }

        if ($this->fechaFin) {
            $finLimpio = Carbon::parse($this->fechaFin)->format('Y-m-d');
            $query->where('created_at', '<=', $finLimpio . ' 23:59:59');
        }

        $selects = [];
        $groupBy = null;

        if ($this->periodo === 'semanal') {
            $selects[] = DB::raw("to_char(created_at, 'IYYY-IW') as etiqueta");
            $groupBy = 'etiqueta';
        } elseif ($this->periodo === 'mensual') {
            $selects[] = DB::raw("to_char(created_at, 'YYYY-MM') as etiqueta");
            $groupBy = 'etiqueta';
        } elseif ($this->periodo === 'diario') {
            $selects[] = DB::raw("to_char(created_at, 'YYYY-MM-DD') as etiqueta");
            $groupBy = 'etiqueta';
        } elseif ($this->periodo === '1min') {
            $selects[] = DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI') as etiqueta");
            $groupBy = 'etiqueta';
        } elseif ($this->periodo === '5min') {
            $selects[] = DB::raw("to_char(to_timestamp(floor((extract('epoch' from created_at) / 300 )) * 300), 'YYYY-MM-DD HH24:MI') as etiqueta");
            $groupBy = 'etiqueta';
        }

        $camposPermitidos = ['eac_Total', 'eac_Tar_1', 'eac_Tar_2', 'Max_demanda', 'eric_Total'];

        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            $alias = "val_{$campo}";
            $selects[] = DB::raw("MAX(NULLIF(\"$campo\"::text, '')::numeric) as \"$alias\"");
        }

        $query->select($selects);
        if ($groupBy) $query->groupBy($groupBy);

        $resultados = $query->orderBy('etiqueta', 'asc')->get();

        $datasets = [];
        $indexColor = 0;

        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;

            $alias = "val_{$campo}";
            $color = $this->colores[$indexColor % count($this->colores)];

            $datasets[] = [
                'label' => ucfirst(str_replace('_', ' ', $campo)),
                'data' => $resultados->pluck($alias)->map(fn ($val) => (float) $val)->toArray(),
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'fill' => false,
                'tension' => 0.2,
                'pointRadius' => count($resultados) > 50 ? 1 : 3,
                'borderWidth' => 2,
            ];
            $indexColor++;
        }

        return [
            'datasets' => $datasets,
            'labels' => $resultados->map(function($reg) {
                try {
                    $fecha = Carbon::parse($reg->etiqueta);
                    if (in_array($this->periodo, ['1min', '5min'])) {
                        if ($fecha->isToday()) return $fecha->format('H:i');
                        return $fecha->format('d/m H:i');
                    } elseif ($this->periodo === 'diario') {
                        return $fecha->format('d/m');
                    }
                    return $reg->etiqueta;
                } catch (\Exception $e) {
                    return $reg->etiqueta;
                }
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
