<?php

namespace App\Filament\Pages;

use App\Models\Medidor;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
// Importamos los widgets (aunque ya no los usaremos en getHeaderWidgets, se usan en la vista)
use App\Filament\Widgets\MedidorEacTotalChart;
use App\Filament\Widgets\MedidorStats;

class AnalisisMedidor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'analisis-medidor/{medidor}';
    protected string $view = 'filament.pages.analisis-medidor';

    public Medidor $medidor;
    public array $campos_grafica = [];

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?string $periodo = 'diario';
    public int $chartKey = 0;

    public function mount(Medidor $medidor): void
    {
        $this->medidor = $medidor;
        $this->campos_grafica = request()->query('campos', ['eac_Total']);

        $this->fecha_inicio = now()->format('Y-m-d');
        $this->fecha_fin = now()->format('Y-m-d');

        $this->form->fill([
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'periodo' => '1min',
        ]);
    }

    // --- BORRA LA FUNCIÓN getHeaderWidgets() COMPLETAMENTE ---
    // protected function getHeaderWidgets(): array { ... }  <-- ELIMINAR ESTO

    protected function getFormSchema(): array
    {
        return [
            Section::make('')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            DatePicker::make('fecha_inicio')->label('Inicio')->native(false)->required(),
                            DatePicker::make('fecha_fin')->label('Fin')->native(false)->required(),
                            Select::make('periodo')
                                ->options([
                                    '1min' => 'Tiempo Real (1 min)',
                                    '5min' => 'Tiempo Real (5 min)',
                                    'diario' => 'Diario',
                                    'semanal' => 'Semanal',
                                    'mensual' => 'Mensual'
                                ])
                                ->native(false)
                                ->required(),

                            Actions::make([
                                Action::make('filtrar')
                                    ->label('Actualizar')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('primary')
                                    ->action(function () {
                                        $data = $this->form->getState();
                                        $this->fecha_inicio = $data['fecha_inicio'];
                                        $this->fecha_fin = $data['fecha_fin'];
                                        $this->periodo = $data['periodo'];

                                        $this->chartKey++; // Esto actualizará AMBOS widgets en la vista
                                    }),
                            ])->alignCenter(),
                        ]),
                ])
        ];
    }
}
