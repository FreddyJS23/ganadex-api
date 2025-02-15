<table class="tabla_natalidad">
    <thead>
        <tr>
            <th>
                <p>Mes</p>
            </th>
            <th>
                <p>Machos</p>
            </th>
            <th>
                <p>Hembras</p>
            </th>
            <th>
                <p>Total</p>
            </th>
        </tr>
    </thead>


    <tbody>
        @forelse ($nacimientosPorMeses as $mes => $nacimiento)
            <tr class="fila_celdas">
                <td class="celda_tabla">
                    <p>{{ $nacimiento['mes'] }} </p>
                </td>

                <td class="celda_tabla">
                    <p>{{ $nacimiento['machos'] }} </p>
                </td>

                <td class="celda_tabla">
                    <p>{{ $nacimiento['hembras'] }} </p>
                </td>

                <td class="celda_tabla">
                    <p>{{ $nacimiento['total'] }} </p>
                </td>

            </tr>
        @empty <tr>
                <td class="celda_sin_info">
                    <p>No tiene</p>
                </td>
            </tr>
        @endforelse

    </tbody>
</table>
