<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AnalisisVersus;
use App\Models\Departamento;
use App\Models\Almacen;
use App\Models\Medidor;
use Filament\Actions\Action;
// --- IMPORTS OBLIGATORIOS PARA QUE EL BOTÓN FUNCIONE ---
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
// -------------------------------------------------------

use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\Widget;

// AGREGAMOS HasActions AQUÍ
class SeleccionVersusMedidores extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions; // AGREGAMOS EL TRAIT AQUÍ

    protected string $view = 'filament.widgets.seleccion-versus-medidores';
    protected int | string | array $columnSpan = 'full';

    public ?int $departamento_id = null;
    public ?int $almacen_id = null;
    public ?int $medidor_id = null;
    public ?string $tipo_vs = null;
    public array $campos_izq = [];
    public array $campos_der = [];

    protected array $reglasVersus = [
        'v_ln_vs_curr' => [
            'label' => 'Voltaje (L-N) vs Corriente',
            'izq'   => ['volt_l1_neutro' => 'Volt L1-N', 'volt_l2_neutro' => 'Volt L2-N', 'volt_l3_neutro' => 'Volt L3-N'],
            'der'   => ['corr_l1' => 'Corriente L1', 'corr_l2' => 'Corriente L2', 'corr_l3' => 'Corriente L3'],
        ],
        'v_ll_vs_pow' => [
            'label' => 'Voltaje (L-L) vs Potencia Activa',
            'izq'   => ['volt_l1l2' => 'Volt L1-L2', 'volt_l2l3' => 'Volt L2-L3', 'volt_l3l1' => 'Volt L3-L1'],
            'der'   => ['pont_act_l1' => 'Potencia L1', 'pont_act_l2' => 'Potencia L2', 'pont_act_l3' => 'Potencia L3'],
        ],
        'curr_vs_pow' => [
            'label' => 'Corriente vs Potencia Activa',
            'izq'   => ['corr_l1' => 'Corriente L1', 'corr_l2' => 'Corriente L2', 'corr_l3' => 'Corriente L3'],
            'der'   => ['pont_act_l1' => 'Potencia L1', 'pont_act_l2' => 'Potencia L2', 'pont_act_l3' => 'Potencia L3'],
        ],
        'tot_vs_tot' => [
            'label' => 'Potencia Total vs Energía Total',
            'izq'   => ['pont_act_total' => 'Potencia Activa Total'],
            'der'   => ['ener_act_total' => 'Energía Activa Total'],
        ],
    ];

    protected function getFormSchema(): array
    {
        return [
            Section::make('Configuración del Versus')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('departamento_id')
                                ->label('Departamento')
                                ->options(Departamento::where('estado_departamento', 1)->pluck('nombre_departamento', 'id_departamento'))
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('almacen_id', null); $set('medidor_id', null);
                                })
                                ->required(),

                            Select::make('almacen_id')
                                ->label('Almacén')
                                ->options(fn (Get $get) => $get('departamento_id')
                                    ? Almacen::where('id_departamento', $get('departamento_id'))->pluck('nombre_almacen', 'id_almacen')
                                    : []
                                )
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('medidor_id', null))
                                ->required()
                                ->disabled(fn (Get $get) => ! $get('departamento_id')),

                            Select::make('medidor_id')
                                ->label('Medidor')
                                ->options(fn (Get $get) => $get('almacen_id')
                                    ? Medidor::where('id_almacen', $get('almacen_id'))->get()->unique('cod_medidor')->pluck('cod_medidor', 'id_medidor')
                                    : []
                                )
                                ->live()
                                ->required()
                                ->disabled(fn (Get $get) => ! $get('almacen_id')),
                        ]),

                    Select::make('tipo_vs')
                        ->label('Tipo de Comparación (Versus)')
                        ->options(collect($this->reglasVersus)->mapWithKeys(fn($item, $key) => [$key => $item['label']]))
                        ->live()
                        ->afterStateUpdated(function (callable $set) {
                            $set('campos_izq', []);
                            $set('campos_der', []);
                        })
                        ->required()
                        ->visible(fn (Get $get) => filled($get('medidor_id')))
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->visible(fn (Get $get) => filled($get('tipo_vs')))
                        ->schema([
                            Section::make('Lado Izquierdo')
                                ->schema([
                                    CheckboxList::make('campos_izq')
                                        ->hiddenLabel()
                                        ->options(function (Get $get) {
                                            $tipo = $get('tipo_vs');
                                            return $tipo ? $this->reglasVersus[$tipo]['izq'] : [];
                                        })
                                        ->required()
                                        ->minItems(1)
                                        ->live()
                                        ->columns(1),
                                ])->columnSpan(1),

                            Section::make('Lado Derecho')
                                ->schema([
                                    CheckboxList::make('campos_der')
                                        ->hiddenLabel()
                                        ->options(function (Get $get) {
                                            $tipo = $get('tipo_vs');
                                            return $tipo ? $this->reglasVersus[$tipo]['der'] : [];
                                        })
                                        ->required()
                                        ->minItems(1)
                                        ->live()
                                        ->columns(1),
                                ])->columnSpan(1),
                        ]),

                    Actions::make([
                        Action::make('generar_vs')
                            ->label('Generar Versus')
                            ->icon('heroicon-m-arrows-right-left')
                            ->button()
                            ->color('danger')
                            ->action(function (Get $get) {
                                $medidorId = $get('medidor_id');
                                $izq = $get('campos_izq');
                                $der = $get('campos_der');

                                if (!$medidorId || empty($izq) || empty($der)) {
                                    return;
                                }

                                return redirect()->to(
                                    AnalisisVersus::getUrl([
                                        'medidor' => $medidorId,
                                        'left'    => $izq,
                                        'right'   => $der,
                                    ])
                                );
                            })
                            ->disabled(fn (Get $get) =>
                                ! $get('medidor_id') ||
                                empty($get('campos_izq')) ||
                                empty($get('campos_der'))
                            ),
                    ])->alignEnd(),
                ]),
        ];
    }
}
