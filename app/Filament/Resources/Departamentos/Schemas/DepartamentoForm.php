<?php

namespace App\Filament\Resources\Departamentos\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartamentoForm
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
                        TextInput::make('nombre_departamento')
                            ->required(),
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
