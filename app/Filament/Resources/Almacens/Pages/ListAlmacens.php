<?php

namespace App\Filament\Resources\Almacens\Pages;

use App\Filament\Resources\Almacens\AlmacenResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ListAlmacens extends ListRecords
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo almacen')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear almacen')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalWidth('3xl')
                    ->successNotificationTitle('La almacen ha sido creada correctamente')
                    ->form([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('nombre_almacen')
                                    ->label('Nombre almacen')
                                    ->unique()
                                    ->required(),

                                TextInput::make('ip')
                                    ->label('IP')
                                    ->unique()
                                    ->required(),

                                TextInput::make('port')
                                    ->label('Puerto')
                                    ->required(),
                            ]),
                        ])

                    ->mutateFormDataUsing(function (array $data) {
                        $data['estado_almacen'] = 1;
                        $data['id_departamento'] = 1;
                        $data['user_created'] = Auth::id();
                        $data['user_updated'] = Auth::id();
                        return $data;
                    }),
        ];
    }
}
