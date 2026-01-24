<x-filament::page>
    <div class="space-y-6">

        {{-- 1. FILTROS GLOBALES --}}
        <div class="w-full bg-white dark:bg-gray-900 p-4 rounded-xl shadow border border-gray-200 dark:border-gray-700">
            {{ $this->form }}
        </div>

        {{--
            2. CONTENEDOR GRID VERSUS
            - md:grid-cols-2: Divide la pantalla en 2 columnas iguales (50% - 50%)
            - items-stretch: OBLIGATORIO para que ambas cajas tengan la misma altura exacta.
        --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">

            {{-- LADO IZQUIERDO (GRUPO A) --}}
            <div class="flex flex-col h-full">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full min-h-[500px]">

                    {{-- <h3 class="text-lg font-bold text-center text-primary-600 dark:text-primary-400 mb-2 border-b border-gray-100 dark:border-gray-700 pb-2">
                        LADO IZQUIERDO
                    </h3> --}}
                    <br>

                    {{-- Contenedor de la gráfica: flex-1 para que ocupe todo el alto disponible --}}
                    <div class="flex-1 w-full relative">
                        @livewire(\App\Filament\Widgets\VersusChart::class, [
                            'medidorId'   => $medidor->id_medidor,
                            'campos'      => $campos_left,
                            'fechaInicio' => $fecha_inicio,
                            'fechaFin'    => $fecha_fin,
                            'periodo'     => $periodo
                        ], key('left-' . $chartKey))
                    </div>
                </div>
            </div>

            {{-- LADO DERECHO (GRUPO B) --}}
            <div class="flex flex-col h-full">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full min-h-[500px]">

                    {{-- <h3 class="text-lg font-bold text-center text-danger-600 dark:text-danger-400 mb-2 border-b border-gray-100 dark:border-gray-700 pb-2">
                        LADO DERECHO
                    </h3> --}}
                    <br>

                    {{-- Contenedor de la gráfica --}}
                    <div class="flex-1 w-full relative">
                        @livewire(\App\Filament\Widgets\VersusChart::class, [
                            'medidorId'   => $medidor->id_medidor,
                            'campos'      => $campos_right,
                            'fechaInicio' => $fecha_inicio,
                            'fechaFin'    => $fecha_fin,
                            'periodo'     => $periodo
                        ], key('right-' . $chartKey))
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-filament::page>
