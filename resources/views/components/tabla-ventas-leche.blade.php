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
        @forelse ($ventasLeche as $venta_leche)
            @php
                $gananciaTotal += $venta_leche['ganancia_total'];
            @endphp
            <tr >
                <td >
                    <p>{{ $venta_leche['fecha'] }} </p>
                </td>
                <td >
                    <p>{{ $venta_leche['cantidad'] }}kg </p>
                </td>
                <td>
                    <p>{{ $venta_leche['precio'] }}$ </p>
                </td>
                <td>
                    <p>{{ $venta_leche['ganancia_total'] }}$ </p>
                </td>
            </tr>
        @empty <tr>
                <td class="celda_sin_info">
                    <p>No tiene</p>
                </td>
            </tr>
        @endforelse
        <tr >
            <td colspan="3" ><b>Ganancia acumulada</b></td>
            <td > {{$gananciaTotal}}$ </td>
        </tr>
    </tbody>
</table>
