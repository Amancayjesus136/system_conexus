<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
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
        // Solo refresca automáticamente en vistas de tiempo real
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

        // ----------------------------- 1. Configuración de Tiempos -----------------------------
        // TIEMPO REAL (1min, 5min): Inicio = Fecha seleccionada. Fin = AHORA MISMO (para ir creando la gráfica en vivo)
        // HISTÓRICO (Diario, etc): Inicio = Fecha inicio 00:00. Fin = Fecha fin 23:59.

        $startTime = Carbon::parse($this->fechaInicio);

        if (in_array($this->periodo, ['1min', '5min'])) {
            $startTime = $startTime->startOfMinute(); // Respetamos la hora que venga, solo quitamos segundos
            $endTime = Carbon::now()->startOfMinute();
        } else {
            $startTime = $startTime->startOfDay();
            $endTime = Carbon::parse($this->fechaFin)->endOfDay();
        }

        // ----------------------------- 2. Valor Inicial (Carry Forward) -----------------------------
        // Buscamos el último valor conocido ANTES del inicio para que la gráfica no empiece en 0
        $lastBefore = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->where('created_at', '<', $startTime)
            ->latest('created_at')
            ->first();

        $lastValues = [];
        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;
            // Si existe un registro previo, usamos ese valor. Si no, 0.
            $lastValues["val_{$campo}"] = $lastBefore ? (float)$lastBefore->$campo : 0;
        }

        // ----------------------------- 3. Traer Datos CRUDOS (Sin Group By) -----------------------------
        // Traemos todo lo que hay en el rango y ordenamos por fecha.
        // La magia de los 5min/diario la haremos en el bucle PHP.
        $registros = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->orderBy('created_at', 'asc')
            ->get();

        // ----------------------------- 4. Bucle Maestro (Lógica de Intervalos) -----------------------------
        $labels = [];
        $dataByTime = [];
        foreach ($this->campos as $campo) {
            if (in_array($campo, $camposPermitidos)) $dataByTime["val_{$campo}"] = [];
        }

        $current = $startTime->copy();

        // Definir cuánto sumamos en cada vuelta del bucle
        $addFunction = match($this->periodo) {
            '1min' => fn($c) => $c->addMinute(),
            '5min' => fn($c) => $c->addMinutes(5),
            'diario' => fn($c) => $c->addDay(),
            'semanal' => fn($c) => $c->addWeek(),
            'mensual' => fn($c) => $c->addMonth(),
            default => fn($c) => $c->addDay(),
        };

        // BUCLE: Desde Inicio hasta Fin
        while ($current <= $endTime) {

            // Definimos el final de ESTE bloque de tiempo (ej: de 3:00 a 3:05)
            $nextStep = $current->copy();
            $addFunction($nextStep);

            // Buscamos si hay algún registro en este intervalo específico
            // Ejemplo: Entre 3:02 y 3:07. Si hay uno a las 3:04, lo tomamos.
            $registroEnIntervalo = $registros->filter(function($item) use ($current, $nextStep) {
                $t = Carbon::parse($item->created_at);
                return $t >= $current && $t < $nextStep;
            })->last(); // Tomamos el último de ese intervalo (el más reciente)

            // SI ENCONTRAMOS UN REGISTRO: Actualizamos el "Valor Actual"
            if ($registroEnIntervalo) {
                foreach ($this->campos as $campo) {
                    if (!in_array($campo, $camposPermitidos)) continue;
                    $lastValues["val_{$campo}"] = (float)$registroEnIntervalo->$campo;
                }
            }
            // SI NO HAY REGISTRO: $lastValues se mantiene igual (hace el efecto de línea recta)

            // GUARDAMOS EL DATO EN LA GRÁFICA
            foreach ($this->campos as $campo) {
                if (!in_array($campo, $camposPermitidos)) continue;
                $dataByTime["val_{$campo}"][] = $lastValues["val_{$campo}"];
            }

            // GENERAMOS LA ETIQUETA
            if (in_array($this->periodo, ['1min', '5min'])) {
                $labels[] = $current->format('H:i');
            } elseif ($this->periodo === 'diario') {
                $labels[] = $current->format('d/m');
            } elseif ($this->periodo === 'mensual') {
                $labels[] = $current->format('M Y');
            } else {
                $labels[] = $current->format('d/m H:i');
            }

            // AVANZAMOS EL RELOJ
            $addFunction($current);
        }

        // ----------------------------- 5. Construir Datasets -----------------------------
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
                'pointRadius' => 0, // Sin puntos para que se vea fluido
                'pointHoverRadius' => 4,
                'borderWidth' => 2,
                // 'stepped' => true, // Activa esto si quieres que la linea sea cuadrada (escalón)
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
