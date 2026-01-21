<?php

namespace App\Filament\Resources\Departamentos\Pages;

use App\Filament\Resources\Departamentos\DepartamentoResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListDepartamentos extends ListRecords
{
    protected static string $resource = DepartamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo departamento')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear departamento')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalWidth('3xl')
                    ->successNotificationTitle('La departamento ha sido creada correctamente')
                    ->form([
                        TextInput::make('nombre_departamento')
                            ->label('Nombre departamento')
                            ->unique()
                            ->required(),
                    ])
                    ->mutateFormDataUsing(function (array $data) {
                        $data['estado_departamento'] = 1;
                        $data['user_created'] = Auth::id();
                        $data['user_updated'] = Auth::id();
                        return $data;
                    }),
        ];
    }
}
