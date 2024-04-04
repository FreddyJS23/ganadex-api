<table class="tabla_informacion">
    <thead class="titulo_tabla">
        <tr>
            <th colspan="6">
                <p>{{ $tituloDetalle }}</p>
            </th>
        </tr>
    </thead>

    <tbody>
        <tr class="fila_celdas">

            {{--  verificar que dentro del array no haya otro array --}}
            @if (!is_array(current($detalles)))
                @forelse ($detalles as $detalle)
                    @php
                        $titulo = ucfirst(array_keys($detalles)[$loop->index]);
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

                {{--   arrays donde el titulo esta en una posicion y el contenido en otro
            , ejemplo ['titulo'=>'persona','contenido'=>'juan']   --}}
            @else
                @forelse ($detalles as $detalle)
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
            @endif

        </tr>
    </tbody>
</table>
