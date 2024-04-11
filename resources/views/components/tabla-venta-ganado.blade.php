<table class="tabla_ventas_ganado">
    <thead>
        <tr>
            <th>
                <p>Mes</p>
            </th>
            <th>
                <p>Nº del ganado vendido</p>
            </th>
            <th>
                <p>Total vendido</p>
            </th>
            <th>
                <p>Suma de ventas</p>
            </th>
        </tr>
    </thead>

    @php
        $gananciaTotal = 0;
    @endphp
    <tbody>
        @forelse ($ventasGanado as $mes => $ventas_mes)
            @php
                $ganadoVendidoMes='';
                $sumaAcumuladaVentaMes = 0;
                foreach ($ventas_mes as $key => $venta) {
                    $sumaAcumuladaVentaMes += $venta['precio'];
                    $ganadoVendidoMes=$ganadoVendidoMes . $venta['numero'] . '-';
                }
                $ganadoVendidoMes=rtrim($ganadoVendidoMes,'-');
                $gananciaTotal += $sumaAcumuladaVentaMes;
            @endphp
            <tr>
                <td>
                    <p>{{ $mes }} </p>
                </td>
                <td>
                    <p>{{ $ganadoVendidoMes }} </p>
                </td>
                <td>
                    <p>{{ count($ventas_mes) }} </p>
                </td>
                <td>
                    <p>{{ $sumaAcumuladaVentaMes }}$ </p>
                </td>
            </tr>
        @empty <tr>
                <td class="celda_sin_info">
                    <p>No tiene</p>
                </td>
            </tr>
        @endforelse
        <tr>
            <td colspan="3"><b>Ganancia mensual</b></td>
            <td> {{ $gananciaTotal }}$ </td>
        </tr>
    </tbody>
</table>
