<div class="container_tablas_vacas_productoras">

    <table class="tabla_informacion_pequeña">
        <thead class="titulo_tabla">
            <tr>
                <th colspan="3">
                    <p>{{ $titulo1 }}</p>
                </th>
            </tr>
        </thead>

        <tbody>
            @if (is_array($detalles1))
                <tr class="fila_celdas">
                    @forelse ($detalles1 as $detalle)
                        @php
                            $titulo = ucfirst(array_keys($detalles1)[$loop->index]);
                            $titulo = str_replace('_', ' ', $titulo);
                            //transformar columna de tabla pesos
                            $titulo = str_replace('2year', '2 años', $titulo);
                            $caracterEspecial = $titulo == 'Efectividad' ? '%' : '';

                        @endphp

                        <td class="celda_tabla">
                            <h4>{{ $titulo }}</h4>
                            <p>{{ $detalle . $caracterEspecial }} </p>
                        </td>
                    @empty <td class="celda_sin_info">
                            <p>No tiene</p>
                        </td>
                    @endforelse
                </tr>
            @else
                <tr class="fila_celdas">
                    <td colspan="3" class="celda_tabla">
                        <h4>{{ $detalles1 }}</h4>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <table class="tabla_informacion_pequeña">
        <thead class="titulo_tabla">
            <tr>
                <th colspan="3">
                    <p>{{ $titulo2 }}</p>
                </th>
            </tr>
        </thead>

        <tbody>

            @if (is_array($detalles2))
                <tr class="fila_celdas">
                    @forelse ($detalles2 as $detalle)
                        @php
                            $titulo = ucfirst(array_keys($detalles2)[$loop->index]);
                            $titulo = str_replace('_', ' ', $titulo);
                            //transformar columna de tabla pesos
                            $titulo = str_replace('2year', '2 años', $titulo);
                            $caracterEspecial = $titulo == 'Efectividad' ? '%' : '';

                        @endphp

                        <td class="celda_tabla">
                            <h4>{{ $titulo }}</h4>
                            <p>{{ $detalle . $caracterEspecial }} </p>
                        </td>
                    @empty <td class="celda_sin_info">
                            <p>No tiene</p>
                        </td>
                    @endforelse
                </tr>
            @else
                <tr class="fila_celdas">
                    <td colspan="3" class="celda_tabla">
                        <h4>{{ $detalles2 }}</h4>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
