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
                @forelse ($topVacasProductoras as $vacaProductora)
                    @php

                        $titulo ='Vaca ' . $vacaProductora['numero'];
                        $titulo = str_replace('_', ' ', $titulo);

                        $detalle =$vacaProductora['peso_leche'] . 'kg';
                    @endphp

                    <td class="celda_tabla">
                        <h4>{{ $titulo }}</h4>
                        <p>{{ $detalle }} </p>
                    </td>
                @empty <td class="celda_sin_info">
                        <p>No disponible</p>
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
                @forelse ($topVacasMenosProductoras as $vacaMenosProductora)
                    @php
                         $titulo ='Vaca ' . $vacaProductora['numero'];
                        $titulo = str_replace('_', ' ', $titulo);

                        $detalle =$vacaProductora['peso_leche'] . 'kg';

                    @endphp

                    <td class="celda_tabla">
                        <h4>{{ $titulo }}</h4>
                        <p>{{ $detalle }} </p>
                    </td>
                @empty <td class="celda_sin_info">
                        <p>No disponible</p>
                    </td>
                @endforelse
            </tr>
        </tbody>
    </table>
</div>
