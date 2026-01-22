<x-filament::page>
    {{-- Aumentamos el espacio vertical global de 6 a 12 para que respire --}}
    <div class="space-y-12">

        {{-- ========================================== --}}
        {{-- SECCIÓN 1: FILTROS                         --}}
        {{-- ========================================== --}}
        <div class="w-full">
            {{ $this->form }}
        </div>

        {{-- ========================================== --}}
        {{-- SEPARADOR VISUAL (Línea)                   --}}
        {{-- ========================================== --}}
        <div class="hidden sm:block">
            <div class="py-2">
                <div class="border-t border-gray-200 dark:border-gray-700"></div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- SECCIÓN 2: GRÁFICA                         --}}
        {{-- ========================================== --}}
        <div>
            <br>

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
