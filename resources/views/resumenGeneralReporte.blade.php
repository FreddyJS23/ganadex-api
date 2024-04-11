<x-layouts.layout-pdf titulo-reporte="Resumen general" >

    <x-details-cattle.tabla-detalle titulo-detalle="Cabezas de ganado hembra" :detalles="$tiposGanadoHembra" /> 
    <x-details-cattle.tabla-detalle titulo-detalle="Cabezas de ganado macho" :detalles="$tiposGanadoMacho" /> 

    <x-tablas-vacas-productoras titulo-1="Top vacas productoras" :$topVacasProductoras titulo-2="Top vacas menos productoras" :$topVacasMenosProductoras   />
   
    <x-tablas2x1 titulo-1="Vacas pendientes" :detalles1="$ganadoPendienteAcciones" titulo-2="Cantidad de personal" :detalles2="$totalPersonal"  />
     <x-tabla-promedio-anual-semestral titulo-detalle="Promedio mensual producción de leche 1º semestre" :detalles="$balancePrimerSemestre" /> 
    <x-tabla-promedio-anual-semestral titulo-detalle="Promedio mensual producción de leche 2º semestre" :detalles="$balanceSegundoSemestre" /> 
    
  

</x-layouts.layout-pdf>
