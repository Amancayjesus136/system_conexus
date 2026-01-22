<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedidorEacTotalChart extends ChartWidget
{
    // ... config visual ...
    protected static bool $isDiscovered = false;
    protected ?string $pollingInterval = null;

    public ?int $medidorId = null;
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $periodo = 'diario';
    public array $campos = [];

    protected array $colores = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1',
    ];

    public function getHeading(): string
    {
        if (empty($this->campos)) return 'Seleccione parámetros para visualizar';
        $nombres = array_map(fn($c) => ucfirst(str_replace('_', ' ', $c)), $this->campos);
        return 'Histórico: ' . implode(', ', $nombres);
    }

    protected function getData(): array
    {
        // 1. Validaciones
        if (! $this->medidorId || empty($this->campos)) {
            return ['labels' => [], 'datasets' => []];
        }

        // Obtenemos el medidor base para saber su código Y su almacén
        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        $query = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen); // <--- CORRECCIÓN 1: Filtro por Almacén

        // 2. Filtros de fecha
        if ($this->fechaInicio) {
            $query->whereDate('created_at', '>=', $this->fechaInicio);
        }
        if ($this->fechaFin) {
            $query->whereDate('created_at', '<=', $this->fechaFin);
        }

        $selects = [];
        $groupBy = null;

        // 3. Definir agrupación (Eje X)
        if ($this->periodo === 'semanal') {
            // Postgres: Año-Semana ISO
            $selects[] = DB::raw("to_char(created_at, 'IYYY-IW') as etiqueta");
            $groupBy = 'etiqueta';
        } elseif ($this->periodo === 'mensual') {
            // Postgres: Año-Mes
            $selects[] = DB::raw("to_char(created_at, 'YYYY-MM') as etiqueta");
            $groupBy = 'etiqueta';
        } else {
            // --- CORRECCIÓN 2: Lógica DIARIA ---
            // Antes traíamos la fecha completa con hora.
            // Ahora truncamos al DÍA para poder agrupar.
            $selects[] = DB::raw("to_char(created_at, 'YYYY-MM-DD') as etiqueta");
            $groupBy = 'etiqueta';
        }

        $camposPermitidos = ['eac_Total', 'eac_Tar_1', 'eac_Tar_2', 'Max_demanda', 'eric_Total'];

        // 4. Construir SELECT dinámico
        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;

            $alias = "val_{$campo}";

            // --- CORRECCIÓN 3: Usar MAX() siempre ---
            // Como son lecturas acumulativas, el "último" registro del día
            // siempre tendrá el valor más alto (MAX).
            // Usamos MAX() para Diario, Semanal y Mensual.

            // Explicación: Si usas SUM() en medidores acumulativos, la gráfica se dispara erróneamente.
            $selects[] = DB::raw("MAX(NULLIF(\"$campo\"::text, '')::numeric) as \"$alias\"");
        }

        $query->select($selects);

        // Siempre agrupamos (ahora incluso en diario)
        $query->groupBy($groupBy);

        $resultados = $query->orderBy('etiqueta', 'asc')->get();

        // 5. Construir los Datasets
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
                'tension' => 0.1,
                'pointRadius' => 3,
                'borderWidth' => 2,
            ];
            $indexColor++;
        }

        return [
            'datasets' => $datasets,
            'labels' => $resultados->map(function($reg) {
                // Formateo visual de la etiqueta
                if ($this->periodo === 'diario') {
                    // La etiqueta viene como YYYY-MM-DD, la mostramos como dd/mm
                    try {
                        return Carbon::parse($reg->etiqueta)->format('d/m');
                    } catch (\Exception $e) {
                        return $reg->etiqueta;
                    }
                }
                return $reg->etiqueta;
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
