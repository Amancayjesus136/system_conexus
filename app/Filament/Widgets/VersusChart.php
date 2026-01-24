<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class VersusChart extends ChartWidget
{
    protected static bool $isDiscovered = false; // No mostrar en el dashboard principal
    protected ?string $maxHeight = '100%';

    public ?int $medidorId = null;
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $periodo = 'diario';
    public array $campos = []; // Recibe los campos dinámicamente

    protected array $colores = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1',
    ];

    protected function getPollingInterval(): ?string
    {
        if (in_array($this->periodo, ['1min', '5min', '1hora'])) {
            return '5s';
        }
        return null;
    }

    public function getHeading(): string
    {
        if (empty($this->campos)) return 'Seleccione parámetros';
        return ucfirst($this->periodo);
    }

    protected function getData(): array
    {
        if (!$this->medidorId || empty($this->campos)) {
            return ['labels' => [], 'datasets' => []];
        }

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        // START TIME: Siempre 00:00:00 del día de inicio
        $startTime = Carbon::parse($this->fechaInicio)->startOfDay();

        // END TIME: Depende si es histórico o tiempo real
        if (in_array($this->periodo, ['1min', '5min', '1hora', '6horas', '12horas'])) {
            $endTime = Carbon::now();
        } else {
            $endTime = Carbon::parse($this->fechaFin)->endOfDay();
        }

        // CARRY FORWARD: Buscar último valor antes de las 00:00
        $lastBefore = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->where('created_at', '<', $startTime)
            ->latest('created_at')
            ->first();

        $lastValues = [];
        foreach ($this->campos as $campo) {
            $lastValues["val_{$campo}"] = $lastBefore ? (float)$lastBefore->$campo : 0;
        }

        // QUERY DATOS CRUDOS
        $registros = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->orderBy('created_at', 'asc')
            ->get();

        // BUCLE MAESTRO
        $labels = [];
        $dataByTime = [];
        foreach ($this->campos as $campo) $dataByTime["val_{$campo}"] = [];

        $current = $startTime->copy();

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
            $nextStep = $current->copy();
            $addFunction($nextStep);

            // Buscar en intervalo
            $registroEnIntervalo = $registros->filter(function($item) use ($current, $nextStep) {
                $t = Carbon::parse($item->created_at);
                return $t >= $current && $t < $nextStep;
            })->last();

            // Actualizar si hay dato
            if ($registroEnIntervalo) {
                foreach ($this->campos as $campo) {
                    $lastValues["val_{$campo}"] = (float)$registroEnIntervalo->$campo;
                }
            }

            // Guardar dato
            foreach ($this->campos as $campo) {
                $dataByTime["val_{$campo}"][] = $lastValues["val_{$campo}"];
            }

            // Etiquetas
            if (in_array($this->periodo, ['1min', '5min', '1hora'])) {
                $labels[] = $current->format('H:i');
            } elseif (in_array($this->periodo, ['6horas', '12horas', 'diario'])) {
                $labels[] = $current->format('d/m H:i');
            } else {
                $labels[] = $current->format('d/m');
            }

            $addFunction($current);
        }

        // Construir Datasets
        $datasets = [];
        $indexColor = 0;
        foreach ($this->campos as $campo) {
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
