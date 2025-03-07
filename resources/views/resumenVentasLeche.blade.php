<x-layouts.layout-pdf titulo-reporte="Resumen ventas de leche del {{ $inicio }} al {{ $fin }}" :$nombreHacienda>


    <x-tabla-ventas-leche :$ventasLeche />


</x-layouts.layout-pdf>
