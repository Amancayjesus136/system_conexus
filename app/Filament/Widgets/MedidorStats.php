<?php

namespace App\Filament\Widgets;

use App\Models\Medidor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MedidorStats extends BaseWidget
{
    public ?int $medidorId = null;
    public array $campos = [];

    // Configuramos el polling dinámico a 5 segundos siempre
    protected function getPollingInterval(): ?string
    {
        return '5s';
    }

    protected function getStats(): array
    {
        // ... (Tu lógica de stats está correcta, mantenla igual) ...
        if (! $this->medidorId || empty($this->campos)) {
            return [Stat::make('Estado', 'Seleccione un medidor')->color('gray')];
        }

        $medidorBase = Medidor::find($this->medidorId);
        if (!$medidorBase) return [];

        $ultimosRegistros = Medidor::where('cod_medidor', $medidorBase->cod_medidor)
            ->where('id_almacen', $medidorBase->id_almacen)
            ->latest('created_at')
            ->take(2)
            ->get();

        $actual = $ultimosRegistros->first();
        $anterior = $ultimosRegistros->count() > 1 ? $ultimosRegistros->last() : null;

        $stats = [];
        $camposPermitidos = ['eac_Total', 'eac_Tar_1', 'eac_Tar_2', 'Max_demanda', 'eric_Total'];

        foreach ($this->campos as $campo) {
            if (!in_array($campo, $camposPermitidos)) continue;

            $valorActual = $actual ? $actual->$campo : 0;
            $valorAnterior = $anterior ? $anterior->$campo : 0;

            $diferencia = $valorActual - $valorAnterior;
            $descripcion = $diferencia > 0
                ? "Subió " . number_format($diferencia, 2)
                : "Sin cambios";

            $icono = $diferencia > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus';
            $color = $diferencia > 0 ? 'success' : 'gray';

            $stats[] = Stat::make(ucfirst(str_replace('_', ' ', $campo)), number_format($valorActual, 2))
                ->description($descripcion)
                ->descriptionIcon($icono)
                ->color($color)
                ->chart($anterior ? [(float)$valorAnterior, (float)$valorActual] : []);
        }

        return $stats;
    }
}
