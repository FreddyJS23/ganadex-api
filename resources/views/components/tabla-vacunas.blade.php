<h3 style="width:30rem;margin:auto;margin-top:2rem;margin-bottom:1rem">Resumen de vacunas aplicadas</h3>
<table class="tabla_ventas_leche">
    <thead >
        <tr>
            <th>
                <p>Vacuna</p>
            </th>
            <th>
                <p>Veces aplicada</p>
            </th>
            <th>
                <p>Ultima dosis</p>
            </th>
            <th>
                <p>Proxima dosis</p>
            </th>
        </tr>
    </thead>

    <tbody>
        @forelse ($vacunas as $vacuna)

            <tr >
                <td >
                    <p>{{ $vacuna['vacuna'] }} </p>
                </td>
                <td >
                    <p>{{ $vacuna['cantidad'] }} </p>
                </td>
                <td>
                    <p>{{ $vacuna['ultima_dosis'] }}</p>
                </td>
                <td>
                    <p>{{ $vacuna['prox_dosis'] }}</p>
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
