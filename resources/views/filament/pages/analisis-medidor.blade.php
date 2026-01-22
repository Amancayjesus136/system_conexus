<x-filament::page>
    <div class="space-y-8">

        {{-- SECCIÓN 1: FILTROS --}}
        <div class="w-full">
            {{ $this->form }}
        </div>

        <div class="hidden sm:block">
            <div class="py-2">
                <div class="border-t border-gray-200 dark:border-gray-700"></div>
            </div>
        </div>

        {{-- SECCIÓN 2: KPIs (STATS) --}}
        {{-- Aquí renderizamos manualmente los Stats pasando los parámetros --}}
        <div class="w-full">
            @livewire(\App\Filament\Widgets\MedidorStats::class, [
                'medidorId' => $medidor->id_medidor,
                'campos'    => $campos_grafica,
            ], key('stats-' . $chartKey))
            {{-- Usamos la misma chartKey para que al filtrar, los stats también se recarguen --}}
        </div>

        {{-- SECCIÓN 3: GRÁFICA --}}
        <div>
            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-4">
                Gráfica de Tendencia
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 dark:bg-gray-900 dark:border-gray-700 overflow-hidden">
                    @livewire(\App\Filament\Widgets\MedidorEacTotalChart::class, [
                        'medidorId'   => $medidor->id_medidor,
                        'fechaInicio' => $fecha_inicio,
                        'fechaFin'    => $fecha_fin,
                        'periodo'     => $periodo,
                        'campos'      => $campos_grafica,
                    ], key('chart-' . $chartKey))
                </div>
            </div>
        </div>

    </div>
</x-filament::page>
