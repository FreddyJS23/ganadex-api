<x-layouts.layout-pdf titulo-reporte="Informe de ventas ganado del año {{ $year }}" :$nombreHacienda>


    <x-tabla-venta-ganado :$ventasGanado />


</x-layouts.layout-pdf>
