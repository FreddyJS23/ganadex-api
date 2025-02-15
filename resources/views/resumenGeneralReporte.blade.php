<x-layouts.layout-pdf titulo-reporte="Resumen general">

    <x-details-cattle.tabla-detalle titulo-detalle="Vacas" :detalles="$vacas" />
    <x-details-cattle.tabla-detalle titulo-detalle="Toros" :detalles="$toros" />
    <x-details-cattle.tabla-detalle titulo-detalle="Ganado descarte" :detalles="$ganadoDescarte" />
    <x-tablas2x1 titulo-1="Natalidad anual" :detalles1="$natalidad" titulo-2="Mortalidad anual" :detalles2="$mortalidad" />

    <x-tablas-vacas-productoras titulo-1="Top vacas productoras" :$topVacasProductoras
        titulo-2="Top vacas menos productoras" :$topVacasMenosProductoras />

    <x-tablas2x1 titulo-1="Vacas pendientes" :detalles1="$ganadoPendienteAcciones" titulo-2="Cantidad de personal" :detalles2="$totalPersonal" />
    <x-tabla-promedio-anual-semestral titulo-detalle="Promedio mensual producción de leche 1º semestre"
        :detalles="$balancePrimerSemestre" />
    <x-tabla-promedio-anual-semestral titulo-detalle="Promedio mensual producción de leche 2º semestre"
        :detalles="$balanceSegundoSemestre" />



</x-layouts.layout-pdf>
