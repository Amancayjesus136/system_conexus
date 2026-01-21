<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;

class MedidorEacTotalChart extends ChartWidget
{
    protected ?string $heading = 'EAC Total del Medidor';

    protected int | string | array $columnSpan = 'full';

    public ?int $medidorId = null;

    protected function getData(): array
    {
        $medidor = Medidor::find($this->medidorId);

        if (! $medidor) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        return [
            'labels' => ['EAC Total'],
            'datasets' => [
                [
                    'label' => 'Consumo',
                    'data' => [(float) $medidor->eac_Total],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
