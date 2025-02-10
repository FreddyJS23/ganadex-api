<x-layouts.layout-pdf titulo-reporte="Cabeza ganado {{$ganadoInfo['numero']}}" >

    <x-details-cattle.tabla-detalle titulo-detalle="Información" :detalles="$ganadoInfo" />
    <x-details-cattle.tabla-detalle titulo-detalle="Pesos" :detalles="$ganadoPeso" />
    <x-details-cattle.tabla-detalle titulo-detalle="Servicios" :detalles="$ganadoServicio" />
    <x-details-cattle.tabla-detalle titulo-detalle="Partos" :detalles="$ganadoParto" />
    <x-details-cattle.tabla-detalle titulo-detalle="Pesajes de leche" :detalles="$ganadoPesajeLeche" />
    <x-details-cattle.tabla-detalle titulo-detalle="Revisiones" :detalles="$ganadoRevision" />
    <x-tabla-vacunas titulo-detalle="Vacunas" :vacunas="$vacunas" />

</x-layouts.layout-pdf>
