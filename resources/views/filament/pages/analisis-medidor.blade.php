<x-filament::page>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold tracking-tight">
            Análisis del Medidor: <span class="text-primary-600">{{ $medidor->cod_medidor }}</span>
        </h2>

        {{-- Renderizamos los filtros aquí arriba --}}
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            {{ $this->form }}
        </div>

        {{-- Los widgets de la gráfica aparecerán aquí automáticamente --}}
    </div>
</x-filament::page>
