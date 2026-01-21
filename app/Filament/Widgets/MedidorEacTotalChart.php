<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class MedidorEacTotalChart extends ChartWidget
{
    protected ?string $heading = 'EAC Total del Medidor';

    protected int | string | array $columnSpan = 'full';

    public ?int $medidorId = null;

    #[On('medidor-seleccionado')]
    public function setMedidor(int $medidorId): void
    {
        $this->medidorId = $medidorId;

        // ✅ Forma correcta en Livewire v3 / Filament v4
        $this->dispatch('$refresh');
    }

    protected function getData(): array
    {
        if (! $this->medidorId) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

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
