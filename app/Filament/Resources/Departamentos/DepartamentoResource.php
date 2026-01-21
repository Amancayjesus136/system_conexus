<?php

namespace App\Filament\Resources\Departamentos;

use App\Filament\Resources\Departamentos\Pages\CreateDepartamento;
use App\Filament\Resources\Departamentos\Pages\EditDepartamento;
use App\Filament\Resources\Departamentos\Pages\ListDepartamentos;
use App\Filament\Resources\Departamentos\Schemas\DepartamentoForm;
use App\Filament\Resources\Departamentos\Tables\DepartamentosTable;
use App\Models\Departamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DepartamentoResource extends Resource
{
    protected static ?string $model = Departamento::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';
    protected static UnitEnum|string|null $navigationGroup = 'Configuracion';
    protected static ?string $navigationLabel = 'Departamentos';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'departamentos';

    public static function form(Schema $schema): Schema
    {
        return DepartamentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartamentosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartamentos::route('/'),
            // 'create' => CreateDepartamento::route('/create'),
            'edit' => EditDepartamento::route('/{record}/edit'),
        ];
    }
}
