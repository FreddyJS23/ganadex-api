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
            <tr class="fila_celdas">
                @forelse ($detalles2 as $detalle)
                  @php
                        $titulo = ucfirst(array_values($detalle)[0]);
                        $detalle = array_values($detalle)[1];
                        $titulo = str_replace('_', ' ', $titulo);
                        //transformar columna de tabla pesos
                        $titulo = str_replace('2year', '2 años', $titulo);
                        //pluralizar palabras cantidad ganado exeptuando total
                        $titulo = $titulo == 'Total' ? $titulo : $titulo . 's';

                    @endphp

                    <td class="celda_tabla">
                        <h4>{{ $titulo }}</h4>
                        <p>{{ $detalle }} </p>
                    </td>
                @empty <td class="celda_sin_info">
                        <p>No tiene</p>
                    </td>
                @endforelse
            </tr>
        </tbody>
    </table>
</div>
