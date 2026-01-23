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
        if (!$this->medidorId || empty($this->campos)) {
            return ['labels' => [], 'datasets' => []];
        }

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        $camposPermitidos = ['eac_Total', 'eac_Tar_1', 'eac_Tar_2', 'Max_demanda', 'eric_Total'];

        // ----------------------------- Configuración de tiempo -----------------------------
        $startTime = Carbon::parse($this->fechaInicio)->startOfMinute();
        $endTime = $this->periodo === '1min' || $this->periodo === '5min'
            ? Carbon::now()->startOfMinute() // Hasta el momento actual para tiempo real
            : Carbon::parse($this->fechaFin)->endOfDay();

        $intervalMinutes = $this->periodo === '5min' ? 5 : 1;

        // ----------------------------- Último registro antes del inicio -----------------------------
        $lastBefore = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->where('created_at', '<', $startTime)
            ->latest('created_at')
            ->first();

        $lastValues = [];
        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            $lastValues["val_{$campo}"] = $lastBefore ? (float)$lastBefore->$campo : 0;
        }

        // ----------------------------- Traer registros dentro del rango -----------------------------
        $query = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->whereBetween('created_at', [$startTime, $endTime]);

        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            $query->addSelect(DB::raw("MAX(NULLIF(\"$campo\"::text, '')::numeric) as val_{$campo}"));
        }

        if ($this->periodo === '1min') {
            $query->addSelect(DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI') as etiqueta"))->groupBy('etiqueta');
        } elseif ($this->periodo === '5min') {
            $query->addSelect(DB::raw("to_char(to_timestamp(floor((extract('epoch' from created_at) / 300 )) * 300), 'YYYY-MM-DD HH24:MI') as etiqueta"))->groupBy('etiqueta');
        } elseif ($this->periodo === 'diario') {
            $query->addSelect(DB::raw("to_char(created_at, 'YYYY-MM-DD') as etiqueta"))->groupBy('etiqueta');
        } elseif ($this->periodo === 'semanal') {
            $query->addSelect(DB::raw("to_char(created_at, 'IYYY-IW') as etiqueta"))->groupBy('etiqueta');
        } elseif ($this->periodo === 'mensual') {
            $query->addSelect(DB::raw("to_char(created_at, 'YYYY-MM') as etiqueta"))->groupBy('etiqueta');
        }

        $resultados = $query->orderBy('etiqueta', 'asc')->get();

        // ----------------------------- Generar labels y datos forward-fill -----------------------------
        $labels = [];
        $dataByTime = [];
        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            $dataByTime["val_{$campo}"] = [];
        }

        $resultIndex = 0;
        while ($startTime <= $endTime) {
            $labels[] = $startTime->format('Y-m-d H:i');

            $row = $resultados[$resultIndex] ?? null;
            $rowTime = $row ? Carbon::parse($row->etiqueta) : null;

            if ($row && $rowTime <= $startTime) {
                // Actualizamos con registro real
                foreach ($this->campos as $campo) {
                    if (!in_array($campo, $camposPermitidos)) continue;
                    $alias = "val_{$campo}";
                    $lastValues[$alias] = (float)$row->$alias;
                    $dataByTime[$alias][] = $lastValues[$alias];
                }
                $resultIndex++;
            } else {
                // No hay registro: repetimos último valor conocido
                foreach ($this->campos as $campo) {
                    if (!in_array($campo, $camposPermitidos)) continue;
                    $alias = "val_{$campo}";
                    $dataByTime[$alias][] = $lastValues[$alias];
                }
            }

            $startTime->addMinutes($intervalMinutes);
        }

        // ----------------------------- Construir datasets -----------------------------
        $datasets = [];
        $indexColor = 0;
        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            $alias = "val_{$campo}";
            $color = $this->colores[$indexColor % count($this->colores)];

            $datasets[] = [
                'label' => ucfirst(str_replace('_', ' ', $campo)),
                'data' => $dataByTime[$alias],
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'fill' => false,
                'tension' => 0.2,
                'pointRadius' => count($dataByTime[$alias]) > 50 ? 1 : 3,
                'borderWidth' => 2,
            ];
            $indexColor++;
        }

        // ----------------------------- Labels finales -----------------------------
        $formattedLabels = array_map(fn($l) => Carbon::parse($l)->format('H:i'), $labels);

        return [
            'labels' => $formattedLabels,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
