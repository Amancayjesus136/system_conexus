<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AnalisisMedidor;
use App\Models\Departamento;
use App\Models\Almacen;
use App\Models\Medidor;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Widgets\Widget;

class SeleccionMedidores extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.seleccion-medidores';

    protected int | string | array $columnSpan = 'full';

    public ?int $departamento_id = null;
    public ?int $almacen_id = null;
    public ?int $medidor_id = null;

    protected function getFormSchema(): array
    {
        return [

            Select::make('departamento_id')
                ->label('Departamento')
                ->options(
                    Departamento::where('estado_departamento', 1)
                        ->pluck('nombre_departamento', 'id_departamento')
                )
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('almacen_id', null);
                    $set('medidor_id', null);
                })
                ->required(),

            Select::make('almacen_id')
                ->label('Almacén')
                ->options(fn (callable $get) =>
                    $get('departamento_id')
                        ? Almacen::where('id_departamento', $get('departamento_id'))
                            ->where('estado_almacen', 1)
                            ->pluck('nombre_almacen', 'id_almacen')
                        : []
                )
                ->reactive()
                ->afterStateUpdated(fn (callable $set) =>
                    $set('medidor_id', null)
                )
                ->required()
                ->disabled(fn (callable $get) => ! $get('departamento_id')),

            Select::make('medidor_id')
                ->label('Medidor')
                ->options(fn (callable $get) =>
                    $get('almacen_id')
                        ? Medidor::where('id_almacen', $get('almacen_id'))
                            ->where('estado_medidor', 1)
                            ->pluck('cod_medidor', 'id_medidor')
                        : []
                )
                ->reactive()
                ->afterStateUpdated(fn (?int $state) => $state
                    ? redirect()->to(
                        AnalisisMedidor::getUrl([
                            'medidor' => $state,
                        ])
                    )
                    : null
                )

                ->required()
                ->disabled(fn (callable $get) => ! $get('almacen_id')),
        ];
    }
}
