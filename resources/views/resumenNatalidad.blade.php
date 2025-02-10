<x-layouts.layout-pdf titulo-reporte="Resumen de natalidad del año {{$year}}" >

    <x-tabla-natalidad titulo-detalle="Natalidad anual" :nacimientosPorMeses="$nacimientosPorMeses" />
</x-layouts.layout-pdf>
