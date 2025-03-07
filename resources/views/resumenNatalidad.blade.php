<x-layouts.layout-pdf titulo-reporte="Resumen de natalidad del año {{ $year }}" :$nombreHacienda>

    <div class="container_grafico_torta"><img width="300" src='{{ $graficoTorta }}' height="170" id="chartTorta" />
    </div>
    <div class="contenedor_dos_graficos">
        <div class="container_grafico_lineal"><img src={{ $graficoLineal }} width="370" height="170"
                id="chartLineal" /></div>
        <div class="container_grafico_barra"><img src={{ $graficoBarra }} width="370" height="170"
                id="chartBarra" /></div>
    </div>

    <h3 style="margin: auto;width:31rem;margin-bottom:1rem">Nacimientos anual</h3>
    <x-tabla-natalidad titulo-detalle="Natalidad anual" :nacimientosPorMeses="$nacimientosPorMeses" />


</x-layouts.layout-pdf>
