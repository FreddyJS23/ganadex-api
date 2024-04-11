<table class="tabla_ventas_leche">
    <thead >
        <tr>
            <th>
                <p>Fecha</p>
            </th>
            <th>
                <p>Cantidad</p>
            </th>
            <th>
                <p>Precio por kg</p>
            </th>
            <th>
                <p>Ganancia total</p>
            </th>
        </tr>
    </thead>

    @php
        $gananciaTotal = 0;
    @endphp
    <tbody>
        @forelse ($ventasLecheMesActual as $venta_mes)
            @php
                $gananciaTotal += $venta_mes['ganancia_total'];
            @endphp
            <tr >
                <td >
                    <p>{{ $venta_mes['fecha'] }} </p>
                </td>
                <td >
                    <p>{{ $venta_mes['cantidad'] }}kg </p>
                </td>
                <td>
                    <p>{{ $venta_mes['precio'] }}$ </p>
                </td>
                <td>
                    <p>{{ $venta_mes['ganancia_total'] }}$ </p>
                </td>
            </tr>
        @empty <tr>
                <td class="celda_sin_info">
                    <p>No tiene</p>
                </td>
            </tr>
        @endforelse
        <tr >
            <td colspan="3" ><b>Ganancia mensual</b></td>
            <td > {{$gananciaTotal}}$ </td>
        </tr>
    </tbody>
</table>
