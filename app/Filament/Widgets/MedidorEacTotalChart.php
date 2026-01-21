<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedidorEacTotalChart extends ChartWidget
{
    /** 🔒 OCULTO EN DASHBOARD */
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Consumo Detallado por Día';

    protected int | string | array $columnSpan = 'full';

    public ?int $medidorId = null;

    protected function getData(): array
    {
        if (! $this->medidorId) {
            return ['labels' => [], 'datasets' => []];
        }

        $medidorBase = Medidor::find($this->medidorId);

        if (! $medidorBase) {
            return ['labels' => [], 'datasets' => []];
        }

        // Obtenemos todos los registros del medidor para el mes actual
        // Esto traerá tus registros del 18, 19, 20 y los que vengan.
        $resultados = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->select(
                'created_at',
                // Casteo a numeric para evitar el error de PostgreSQL
                DB::raw('NULLIF("eac_Total", \'\')::numeric as valor_eac')
            )
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'EAC Total',
                    'data' => $resultados->pluck('valor_eac')->map(fn ($val) => (float) $val)->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.1, // Línea más recta para ver saltos diarios
                    'pointRadius' => 6,
                ],
            ],
            // Mostramos la fecha y hora o solo el día para cada punto
            'labels' => $resultados->map(function($registro) {
                return Carbon::parse($registro->created_at)->format('d/m H:i');
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
