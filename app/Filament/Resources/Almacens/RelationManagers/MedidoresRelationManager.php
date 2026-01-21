<?php

namespace App\Filament\Resources\Almacens\RelationManagers;

use App\Filament\Resources\Almacens\AlmacenResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MedidoresRelationManager extends RelationManager
{
    protected static string $relationship = 'medidores';

    protected static ?string $relatedResource = AlmacenResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identificación del Medidor')
                ->columns(12)
                ->schema([
                    TextInput::make('cod_medidor')
                        ->label('Código de Medidor')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(12),

                ]),

            Section::make('Lecturas de Energía')
                ->description('Ingrese los valores técnicos registrados')
                ->columns(1)
                ->schema([
                    TextInput::make('eac_Tar_1')
                        ->label('Tarifa 1 (EAC)')
                        ->numeric()
                        ->default(0)
                        ->prefix('kWh'),

                    TextInput::make('eac_Tar_2')
                        ->label('Tarifa 2 (EAC)')
                        ->numeric()
                        ->default(0)
                        ->prefix('kWh'),

                    TextInput::make('eac_Total')
                        ->label('Lectura Total')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->prefix('Σ'),

                    TextInput::make('Max_demanda')
                        ->label('Máxima Demanda')
                        ->numeric()
                        ->default(0)
                        ->prefix('kW'),

                    TextInput::make('eric_Total')
                        ->label('Energía Reactiva')
                        ->numeric()
                        ->default(0)
                        ->prefix('kVARh'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id_almacen')
            ->columns([
                TextColumn::make('cod_medidor')
                    ->label('Codigo medidor')
                    ->searchable(),

                TextColumn::make('eac_Total')
                    ->label('E. activa consumida: Total')
                    ->searchable(),

                TextColumn::make('Max_demanda')
                    ->label('Maxima demanda')
                    ->searchable(),

                TextColumn::make('eric_Total')
                    ->label('E. reactiva inductiva total')
                    ->searchable(),

                TextColumn::make('estado_medidor')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => match ($state) {
                        1 => 'Activo',
                        2 => 'Warning',
                        0 => 'Inactivo',
                    })
                    ->color(fn (int $state) => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        0 => 'danger',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(
                        fn (array $data) => $this->mutateFormDataBeforeCreate($data)
                    ),
            ])

            ->actions([
                // EditAction::make()
                //     ->modalWidth('md')
                //     ->mutateFormDataUsing(fn (array $data) => $this->mutateFormDataBeforeCreate($data)),

                // DeleteAction::make(),
            ])

            ->filters([
                SelectFilter::make('estado_medidor')
                    ->options([
                        1 => 'Integrante',
                        2 => 'Pendiente',
                        0 => 'Rechazado',
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado_medidor'] = 1;
        $data['user_created'] = Auth::id();
        $data['user_updated'] = Auth::id();

        return $data;
    }
}
