<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota de venta</title>
    <style>
         * {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            box-sizing: border-box;
            border-spacing: 0;
        }


        .header_nota_venta {
            margin: auto;
            width: 195px;
            padding-right: 0rem;
            margin-bottom: 10px;
        }
        .header_nota_venta div {
            text-align: center;
            position: relative;
            right: 20px;
            margin:1rem 0;
        }

        .tabla_nota_venta {
            margin: auto;
            width: 290px;
            padding-bottom: 12px;
        }

        .tabla_nota_venta .header_vertical {
            color: rgb(0, 0, 0, 0.8);
        }

        .tabla_nota_venta tr td:nth-last-child(1) {
            text-align: end;
        }

        .tabla_nota_venta td {
            padding-top: 5px;
        }

        .titulo_lista_vacunas{
            margin-top: 20px;
            margin-bottom: 10px;
            margin:auto;
            width:300px
        }

        .contenedor_lista_vacunas{
            width: 300px;
            margin:auto;
            margin-top: 5px
        }
        .elemento_lista_vacunas{
            display:block;
            margin-bottom:2px;
            font-weight: bold
        }

        .footer_nota_venta {
            position: relative;
            margin: auto;
            right: 10px;
            border-top: 1px solid black;
            width: 270px;
        }
        .footer_nota_venta p {
            position: relative;
            right: 10px;
        }
    </style>
</head>

<body>
    <div class="header_nota_venta">
    <div>
            <img src="{{ public_path('logo-light-fuente.png') }}" alt="logo" width="80" />
    </div>
        <h2>Nota de venta</h2>
    </div>
    <table class="tabla_nota_venta">
        <thead>
            <tr>
                <th colspan="4"></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="header_vertical">Numero del animal</td>
                <td></td>
                <td></td>
                <td>{{$numero}}</td>
            </tr>
            <tr>
                <td class="header_vertical">Tipo</td>
                <td></td>
                <td></td>
                <td>{{$tipo}}</td>
            </tr>
            <tr>
                <td class="header_vertical">Peso</td>
                <td></td>
                <td></td>
                <td>{{$peso}}</td>
            </tr>
            <tr>
                <td class="header_vertical">Comprador</td>
                <td></td>
                <td></td>
                <td>{{$comprador}}</td>
            </tr>
            {{-- <tr>
                <td class="header_vertical">Precio</td>
                <td></td>
                <td></td>
                <td>{{$precio}} $</td>
            </tr>
            <tr>
                <td class="header_vertical">Precio por kg</td>
                <td></td>
                <td></td>
                <td>{{$precioKg}} $</td>
            </tr> --}}
        </tbody>
    </table>

    <div class="titulo_lista_vacunas">
        <h3>Vacunas aplicadas</h3>
    </div>

    @forelse ($vacunas as $vacuna )
    @php
        $nombreVacuna = ucfirst(array_keys($vacunas)[$loop->index]);
    @endphp

    <div class="contenedor_lista_vacunas">
        <span class="elemento_lista_vacunas">
            {{$nombreVacuna}}
        </span>
        <span>
            @foreach ($vacuna as $fecha )
            @if($loop->last)
                &bull; {{$fecha}}.
                @else  &bull; {{$fecha}},
                @endif
            @endforeach
        </span>
    </div>
    @empty

    @endforelse

    <footer class="footer_nota_venta">
        <p style="margin-top: 8px; text-align: center">Fecha: @php
            $fechaActual = new DateTime();
            echo $fechaActual->format('Y-m-d');
        @endphp</p>
    </footer>

</body>

</html>
