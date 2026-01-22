<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AnalisisMedidor;
use App\Models\Departamento;
use App\Models\Almacen;
use App\Models\Medidor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
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

    public ?int $departamento_id = null;
    public ?int $almacen_id = null;
    public ?int $medidor_id = null;
    public array $campos_seleccionados = [];

    protected function getFormSchema(): array
    {
        return [
            Grid::make(12)
                ->schema([
                    Select::make('departamento_id')
                        ->label('Departamento')
                        ->options(Departamento::where('estado_departamento', 1)->pluck('nombre_departamento', 'id_departamento'))
                        ->live()
                        ->afterStateUpdated(function (callable $set) {
                            $set('almacen_id', null);
                            $set('medidor_id', null);
                            $set('campos_seleccionados', []);
                        })
                        ->required()
                        ->columnSpan(4),

                    Select::make('almacen_id')
                        ->label('Almacén')
                        ->options(fn (Get $get) => $get('departamento_id')
                            ? Almacen::where('id_departamento', $get('departamento_id'))->pluck('nombre_almacen', 'id_almacen')
                            : []
                        )
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('medidor_id', null))
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('departamento_id'))
                        ->columnSpan(4),

                    Select::make('medidor_id')
                        ->label('Medidor')
                        ->options(fn (Get $get) => $get('almacen_id')
                            ? Medidor::where('id_almacen', $get('almacen_id'))
                                ->get()
                                ->unique('cod_medidor')
                                ->pluck('cod_medidor', 'id_medidor')
                            : []
                        )
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('campos_seleccionados', []))
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('almacen_id'))
                        ->columnSpan(4),

                    CheckboxList::make('campos_seleccionados')
                        ->label('Campos a visualizar')
                        ->options([
                            'eac_Total'   => 'EAC Total',
                            'eac_Tar_1'   => 'EAC Tarifa 1',
                            'eac_Tar_2'   => 'EAC Tarifa 2',
                            'Max_demanda' => 'Máxima Demanda',
                            'eric_Total'  => 'ERIC Total',
                        ])
                        ->columns(5)
                        ->gridDirection('row')
                        ->required()
                        ->visible(fn (Get $get) => filled($get('medidor_id')))
                        ->live()
                        ->columnSpan(12),
                ]),

            Actions::make([
                Action::make('generar')
                    ->label('Generar Gráfica')
                    ->icon('heroicon-m-chart-bar')
                    ->button()
                    ->color('primary')
                    ->action(function (Get $get) {
                        $medidorId = $get('medidor_id');
                        $campos = $get('campos_seleccionados');

                        if ($medidorId && !empty($campos)) {
                            return redirect()->to(
                                AnalisisMedidor::getUrl([
                                    'medidor' => $medidorId,
                                    'campos'  => $campos,
                                ])
                            );
                        }
                    })
                    ->disabled(fn (Get $get) => ! $get('medidor_id') || empty($get('campos_seleccionados'))),
            ])->alignEnd(),
        ];
    }
}
