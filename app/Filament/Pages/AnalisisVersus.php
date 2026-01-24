<?php

namespace App\Filament\Pages;

use App\Models\Medidor;
use Filament\Pages\Page;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Support\Htmlable;

class AnalisisVersus extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'analisis-versus/{medidor}';
    protected string $view = 'filament.pages.analisis-versus'; // Necesitarás crear este blade simple

    public Medidor $medidor;
    public array $campos_left = [];
    public array $campos_right = [];

    // Variables de filtro compartidas
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?string $periodo = 'diario';
    public int $chartKey = 0;

    public function mount(Medidor $medidor): void
    {
        $this->medidor = $medidor;
        // Obtenemos los campos de la URL
        $this->campos_left = request()->query('left', []);
        $this->campos_right = request()->query('right', []);

        // Inicializar fechas (Inicio del día -> Fin del día)
        $this->fecha_inicio = now()->startOfDay()->format('Y-m-d H:i:s');
        $this->fecha_fin = now()->endOfDay()->format('Y-m-d H:i:s');

        $this->form->fill([
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'periodo' => '1min',
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return "Versus: " . $this->medidor->cod_medidor;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Filtros Globales')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            DateTimePicker::make('fecha_inicio')->label('Inicio')->seconds(false)->required(),
                            DateTimePicker::make('fecha_fin')->label('Fin')->seconds(false)->required(),
                            Select::make('periodo')
                                ->options([
                                    '1min' => 'Tiempo Real (1 min)',
                                    '5min' => 'Tiempo Real (5 min)',
                                    '1hora' => 'Cada 1 Hora',
                                    '6horas' => 'Cada 6 Horas',
                                    '12horas' => 'Cada 12 Horas',
                                    'diario' => 'Diario',
                                    'semanal' => 'Semanal',
                                    'mensual' => 'Mensual'
                                ])->required(),

                            Actions::make([
                                Action::make('filtrar')
                                    ->label('Actualizar VS')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('primary')
                                    ->action(function () {
                                        $data = $this->form->getState();
                                        $this->fecha_inicio = $data['fecha_inicio'];
                                        $this->fecha_fin = $data['fecha_fin'];
                                        $this->periodo = $data['periodo'];
                                        $this->chartKey++; // Actualiza AMBAS gráficas
                                    }),
                            ])->alignCenter(),
                        ]),
                ])
        ];
    }
}
