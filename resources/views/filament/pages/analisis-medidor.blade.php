<x-filament::page class="space-y-8">

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Filtros de Estadísticas
        </h3>

        <div wire:submit="applyFilters">
            {{ $this->form }}
        </div>
    </div>

    <div class="hidden sm:block">
        <div class="py-2">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @livewire(\App\Filament\Widgets\MedidorStats::class, [
            'medidorId' => $medidor->id_medidor,
            'campos'    => $campos_grafica,
        ], key('stats-' . $chartKey))
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Gráfica de Tendencia
        </h3>

        @livewire(\App\Filament\Widgets\MedidorEacTotalChart::class, [
            'medidorId'   => $medidor->id_medidor,
            'fechaInicio' => $fecha_inicio,
            'fechaFin'    => $fecha_fin,
            'periodo'     => $periodo,
            'campos'      => $campos_grafica,
        ], key('chart-' . $chartKey))
    </div>

</x-filament::page>
