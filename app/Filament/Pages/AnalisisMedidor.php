<?php

namespace App\Filament\Pages;

use App\Models\Medidor;
use Filament\Pages\Page;
use App\Filament\Widgets\MedidorEacTotalChart;

class AnalisisMedidor extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'analisis-medidor/{medidor}';

    protected string $view = 'filament.pages.analisis-medidor';

    public Medidor $medidor;

    public function mount(Medidor $medidor): void
    {
        $this->medidor = $medidor;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MedidorEacTotalChart::make([
                'medidorId' => $this->medidor->id_medidor,
            ]),
        ];
    }
}
