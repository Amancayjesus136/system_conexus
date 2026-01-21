<?php

namespace App\Filament\Pages;

use App\Models\Medidor;
use Filament\Pages\Page;
use App\Filament\Widgets\MedidorEacTotalChart;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;

class AnalisisMedidor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'analisis-medidor/{medidor}';
    protected string $view = 'filament.pages.analisis-medidor';

    public Medidor $medidor;

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?string $periodo = 'diario';

    // Propiedad para forzar el refresco
    public int $chartKey = 0;

    public function mount(Medidor $medidor): void
    {
        $this->medidor = $medidor;
        $this->fecha_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = now()->format('Y-m-d');

        $this->form->fill([
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'periodo' => $this->periodo,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(4)
                ->schema([
                    DatePicker::make('fecha_inicio')->label('Fecha Inicio')->native(false),
                    DatePicker::make('fecha_fin')->label('Fecha Fin')->native(false),
                    Select::make('periodo')
                        ->label('Agrupar por')
                        ->options(['diario' => 'Diario', 'semanal' => 'Semanal', 'mensual' => 'Mensual'])
                        ->native(false),

                    Actions::make([
                        Action::make('aplicarFiltros')
                            ->label('Aplicar Cambios')
                            ->color('success')
                            ->icon('heroicon-m-funnel')
                            ->action(function () {
                                $state = $this->form->getState();
                                $this->fecha_inicio = $state['fecha_inicio'];
                                $this->fecha_fin = $state['fecha_fin'];
                                $this->periodo = $state['periodo'];

                                // Incrementamos para que la vista detecte el cambio
                                $this->chartKey++;
                            }),
                    ])->columnSpan(1)->alignCenter(),
                ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MedidorEacTotalChart::make([
                'medidorId' => $this->medidor->id_medidor,
                'fechaInicio' => $this->fecha_inicio,
                'fechaFin' => $this->fecha_fin,
                'periodo' => $this->periodo,
            ]),
        ];
    }
}
