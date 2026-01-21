<?php

namespace App\Filament\Resources\Almacens\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlmacenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([

                Section::make('Información principal')
                    ->columns(1)
                    ->columnSpan(8)
                    ->schema([
                        TextInput::make('nombre_almacen')
                            ->label('Nombre de Almacen')
                            ->required(),

                        // TextInput::make('ip')
                        //     ->label('IP')
                        //     ->required(),

                        // TextInput::make('port')
                        //     ->label('Puerto')
                        //     ->required(),
                    ]),

                Section::make('Detalles adicionales')
                    ->columns(1)
                    ->columnSpan(4)
                    ->schema([

                        Placeholder::make('created_at')
                            ->label('Creado')
                            ->content(fn ($record): string => $record?->created_ago ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Última modificación')
                            ->content(fn ($record): string => $record?->updated_ago ?? '-'),
                    ]),

            ]);
    }
}
