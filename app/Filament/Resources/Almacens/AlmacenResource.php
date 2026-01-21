<?php

namespace App\Filament\Resources\Almacens;

use App\Filament\Resources\Almacens\Pages\CreateAlmacen;
use App\Filament\Resources\Almacens\Pages\EditAlmacen;
use App\Filament\Resources\Almacens\Pages\ListAlmacens;
use App\Filament\Resources\Almacens\RelationManagers\MedidoresRelationManager;
use App\Filament\Resources\Almacens\Schemas\AlmacenForm;
use App\Filament\Resources\Almacens\Tables\AlmacensTable;
use App\Models\Almacen;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AlmacenResource extends Resource
{
    protected static ?string $model = Almacen::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static UnitEnum|string|null $navigationGroup = 'Gestión Operativa';
    protected static ?string $navigationLabel = 'Almacenes';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'almacenes';

    public static function form(Schema $schema): Schema
    {
        return AlmacenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlmacensTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MedidoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlmacens::route('/'),
            // 'create' => CreateAlmacen::route('/create'),
            'edit' => EditAlmacen::route('/{record}/edit'),
        ];
    }
}
