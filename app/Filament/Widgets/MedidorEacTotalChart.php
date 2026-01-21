<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedidorEacTotalChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Consumo Histórico';
    protected int | string | array $columnSpan = 'full';

    public ?int $medidorId = null;
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $periodo = 'diario';

    protected function getData(): array
    {
        if (! $this->medidorId) return ['labels' => [], 'datasets' => []];

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return ['labels' => [], 'datasets' => []];

        $query = Medidor::query()
            ->where('cod_medidor', $medidorBase->cod_medidor);

        // Los filtros ahora funcionarán porque la página recrea el widget con estos valores
        if ($this->fechaInicio) {
            $query->whereDate('created_at', '>=', $this->fechaInicio);
        }
        if ($this->fechaFin) {
            $query->whereDate('created_at', '<=', $this->fechaFin);
        }

        // Agrupación Postgres
        if ($this->periodo === 'semanal') {
            $query->select(
                DB::raw("to_char(created_at, 'IYYY-IW') as etiqueta"),
                DB::raw('SUM(NULLIF("eac_Total", \'\')::numeric) as valor_eac')
            )->groupBy('etiqueta');
        } elseif ($this->periodo === 'mensual') {
            $query->select(
                DB::raw("to_char(created_at, 'YYYY-MM') as etiqueta"),
                DB::raw('SUM(NULLIF("eac_Total", \'\')::numeric) as valor_eac')
            )->groupBy('etiqueta');
        } else {
            $query->select(
                'created_at as etiqueta',
                DB::raw('NULLIF("eac_Total", \'\')::numeric as valor_eac')
            );
        }

        $resultados = $query->orderBy('etiqueta', 'asc')->get();

        return [
            'datasets' => [
                [
                    'label' => 'EAC Total',
                    'data' => $resultados->pluck('valor_eac')->map(fn ($val) => (float) $val)->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.1,
                ],
            ],
            'labels' => $resultados->map(function($reg) {
                $date = Carbon::parse($reg->etiqueta);
                return $this->periodo === 'diario' ? $date->format('d/m H:i') : $reg->etiqueta;
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
