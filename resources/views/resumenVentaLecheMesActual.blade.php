<x-layouts.layout-pdf titulo-reporte="Resumen ventas de leche del {{$inicio}} al {{$fin}}"  >


    <x-tabla-venta-mes-leche :$ventasLecheMesActual />
  

</x-layouts.layout-pdf>
