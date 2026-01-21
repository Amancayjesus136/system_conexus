<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AnalisisMedidor;
use App\Models\Departamento;
use App\Models\Almacen;
use App\Models\Medidor;
use Filament\Actions\Action;
// 1. Agregar estos imports de Actions
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\Widget;

// 2. Implementar HasActions y HasForms (separados por coma)
class SeleccionMedidores extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions; // 3. Usar el trait InteractsWithActions

    protected string $view = 'filament.widgets.seleccion-medidores';

    protected int | string | array $columnSpan = 'full';

    public ?int $departamento_id = null;
    public ?int $almacen_id = null;
    public ?int $medidor_id = null;

    protected function getFormSchema(): array
    {
        // ... (El resto de tu código se mantiene igual)
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
                ->options(fn (Get $get) =>
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
                ->disabled(fn (Get $get) => ! $get('departamento_id')),

            Select::make('medidor_id')
                ->label('Medidor')
                ->options(fn (Get $get) =>
                    $get('almacen_id')
                        ? Medidor::where('id_almacen', $get('almacen_id'))
                            ->where('estado_medidor', 1)
                            ->pluck('cod_medidor', 'id_medidor')
                        : []
                )
                ->reactive()
                ->required()
                ->disabled(fn (Get $get) => ! $get('almacen_id')),

            Actions::make([
                Action::make('generar')
                    ->label('Generar Gráfica')
                    ->icon('heroicon-m-chart-bar')
                    ->button()
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
            ])->fullWidth(),
        ];
    }
}
