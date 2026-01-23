<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class MedidorEacTotalChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    // protected static ?string $maxHeight = '100%';

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
        // Refrescar en tiempo real solo para intervalos cortos
        if (in_array($this->periodo, ['1min', '5min', '1hora'])) {
            return '5s';
        }
        return null;
    }

    public function getHeading(): string
    {
        if (empty($this->campos)) return 'Seleccione parámetros';
        return 'Histórico (' . ucfirst($this->periodo) . ')';
    }

    protected function getData(): array
    {
        if (!$this->medidorId || empty($this->campos)) {
            return ['labels' => [], 'datasets' => []];
        }

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        $camposPermitidos = ['eac_Total', 'eac_Tar_1', 'eac_Tar_2', 'Max_demanda', 'eric_Total', 'volt_l1_neutro', 'volt_l2_neutro', 'volt_l3_neutro', 'volt_l1l2', 'volt_l2l3', 'volt_l3l1', 'corr_l1', 'corr_l2', 'corr_l3', 'pont_act_l1', 'pont_act_l2', 'pont_act_l3', 'pont_act_total', 'ener_act_total'];

        // ----------------------------- 1. Configuración de Tiempos -----------------------------

        // REQUISITO: Siempre empezar a las 00:00:00 del día de inicio seleccionado
        $startTime = Carbon::parse($this->fechaInicio)->startOfDay();

        // Configurar Final
        if (in_array($this->periodo, ['1min', '5min', '1hora', '6horas', '12horas'])) {
            // Tiempo Real: Hasta el momento actual
            $endTime = Carbon::now();
        } else {
            // Histórico: Hasta el final del día seleccionado
            $endTime = Carbon::parse($this->fechaFin)->endOfDay();
        }

        // ----------------------------- 2. Valor Inicial (Carry Forward) -----------------------------
        // Buscamos el último valor conocido ANTES de las 00:00:00
        $lastBefore = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->where('created_at', '<', $startTime)
            ->latest('created_at')
            ->first();

        $lastValues = [];
        foreach ($this->campos as $campo) {
            if (in_array($campo, $camposPermitidos)) {
                $lastValues["val_{$campo}"] = $lastBefore ? (float)$lastBefore->$campo : 0;
            }
        }

        // ----------------------------- 3. Datos Crudos -----------------------------
        $registros = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->orderBy('created_at', 'asc')
            ->get();

        // ----------------------------- 4. Bucle Maestro -----------------------------
        $labels = [];
        $dataByTime = [];
        foreach ($this->campos as $campo) {
            if (in_array($campo, $camposPermitidos)) $dataByTime["val_{$campo}"] = [];
        }

        $current = $startTime->copy(); // Empieza en 00:00:00

        // Definir incremento según el filtro seleccionado
        $addFunction = match($this->periodo) {
            '1min' => fn($c) => $c->addMinute(),
            '5min' => fn($c) => $c->addMinutes(5),
            '1hora' => fn($c) => $c->addHour(),
            '6horas' => fn($c) => $c->addHours(6),
            '12horas' => fn($c) => $c->addHours(12),
            'diario' => fn($c) => $c->addDay(),
            'semanal' => fn($c) => $c->addWeek(),
            'mensual' => fn($c) => $c->addMonth(),
            default => fn($c) => $c->addDay(),
        };

        while ($current <= $endTime) {

            // Intervalo actual: de $current a $nextStep
            $nextStep = $current->copy();
            $addFunction($nextStep);

            // Buscar registro en este intervalo
            $registroEnIntervalo = $registros->filter(function($item) use ($current, $nextStep) {
                $t = Carbon::parse($item->created_at);
                return $t >= $current && $t < $nextStep;
            })->last();

            // Actualizar valor si existe dato
            if ($registroEnIntervalo) {
                foreach ($this->campos as $campo) {
                    if (in_array($campo, $camposPermitidos)) {
                        $lastValues["val_{$campo}"] = (float)$registroEnIntervalo->$campo;
                    }
                }
            }

            // Insertar dato
            foreach ($this->campos as $campo) {
                if (in_array($campo, $camposPermitidos)) {
                    $dataByTime["val_{$campo}"][] = $lastValues["val_{$campo}"];
                }
            }

            // Generar Etiqueta
            if (in_array($this->periodo, ['1min', '5min', '1hora'])) {
                $labels[] = $current->format('H:i'); // 00:00, 00:01...
            } elseif (in_array($this->periodo, ['6horas', '12horas', 'diario'])) {
                $labels[] = $current->format('d/m H:i');
            } else {
                $labels[] = $current->format('d/m');
            }

            // Avanzar
            $addFunction($current);
        }

        // ----------------------------- 5. Datasets -----------------------------
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
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
                'borderWidth' => 2,
            ];
            $indexColor++;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
