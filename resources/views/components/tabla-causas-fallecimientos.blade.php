<table class="tabla_ventas_leche">
    <thead>
        <tr>
            @forelse ($causasFallecimientos as $causaFallecimiento)
                @php
                    $titulo = ucfirst($causaFallecimiento['causa']);
                    $titulo = str_replace('_', ' ', $titulo);
                @endphp

                <td>
                    <p>{{ $titulo }} </p>
                </td>
            @empty
                <td class="celda_sin_info">
                    <p>vacio</p>
                </td>
            @endforelse
            <td>
                <p>Total fallecidas </p>
            </td>
        </tr>
    </thead>

    @php
        $totalFallecidos = 0;
    @endphp
    <tbody>

        <tr>
            @forelse ($causasFallecimientos as $causaFallecimiento)
                @php
                    $totalFallecidos += $causaFallecimiento['cantidad'];
                @endphp
                <td>
                    <p>{{ $causaFallecimiento['cantidad'] }} </p>
                </td>
            @empty <td class="celda_sin_info">
                    <p>No tiene</p>
                </td>
            @endforelse

            <td> {{ $totalFallecidos }} </td>
        </tr>


    </tbody>
</table>
