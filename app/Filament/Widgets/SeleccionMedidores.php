<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AnalisisMedidor;
use App\Models\Departamento;
use App\Models\Almacen;
use App\Models\Medidor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\Widget;

class SeleccionMedidores extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected string $view = 'filament.widgets.seleccion-medidores';

    protected int | string | array $columnSpan = 'full';

    // Propiedades públicas para enlazar con el formulario
    public ?int $departamento_id = null;
    public ?int $almacen_id = null;
    public ?int $medidor_id = null;

    protected function getFormSchema(): array
    {
        return [
            // Rejilla principal de 12 columnas
            Grid::make(12)
                ->schema([

                    // Selector de Departamento (Col 4)
                    Select::make('departamento_id')
                        ->label('Departamento')
                        ->options(
                            Departamento::where('estado_departamento', 1)
                                ->pluck('nombre_departamento', 'id_departamento')
                        )
                        ->live() // Reemplaza reactive() en versiones modernas para mejor respuesta
                        ->afterStateUpdated(function (callable $set) {
                            $set('almacen_id', null);
                            $set('medidor_id', null);
                        })
                        ->required()
                        ->columnSpan(4),

                    // Selector de Almacén (Col 4)
                    Select::make('almacen_id')
                        ->label('Almacén')
                        ->options(fn (Get $get) =>
                            $get('departamento_id')
                                ? Almacen::where('id_departamento', $get('departamento_id'))
                                    ->where('estado_almacen', 1)
                                    ->pluck('nombre_almacen', 'id_almacen')
                                : []
                        )
                        ->live()
                        ->afterStateUpdated(fn (callable $set) =>
                            $set('medidor_id', null)
                        )
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('departamento_id'))
                        ->columnSpan(4),

                    // Selector de Medidor (Col 4) - CORREGIDO PARA EVITAR DUPLICADOS
                    Select::make('medidor_id')
                        ->label('Medidor')
                        ->options(fn (Get $get) =>
                            $get('almacen_id')
                                ? Medidor::where('id_almacen', $get('almacen_id'))
                                    ->where('estado_medidor', 1)
                                    ->get()
                                    ->unique('cod_medidor') // Evita mostrar el mismo código varias veces
                                    ->pluck('cod_medidor', 'id_medidor')
                                : []
                        )
                        ->live()
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('almacen_id'))
                        ->columnSpan(4),
                ]),

            // Fila inferior para el botón
            Actions::make([
                Action::make('generar')
                    ->label('Generar Gráfica')
                    ->icon('heroicon-m-chart-bar')
                    ->button()
                    ->color('primary')
                    ->action(function (Get $get) {
                        $medidorId = $get('medidor_id');

                        if ($medidorId) {
                            return redirect()->to(
                                AnalisisMedidor::getUrl([
                                    'medidor' => $medidorId,
                                ])
                            );
                        }
                    })
                    ->disabled(fn (Get $get) => ! $get('medidor_id')),
            ])->alignEnd(), // Lo empuja a la derecha para un look más profesional
        ];
    }
}
