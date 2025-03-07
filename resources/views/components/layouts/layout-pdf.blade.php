<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>{{ $tituloReporte }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            box-sizing: border-box;
            border-spacing: 0;
        }

        .header,
        .footer {
            width: 595px;
            padding: 0.5rem 2rem;
            margin: auto;
        }

        .footer {
            position: absolute;
            bottom: 12px;
            right: 0;
            left: 0;
        }

        .header {
            margin-top: 10px;
            margin-bottom: 12px;
            border-bottom: 1px solid black;
            position: relative;
            height: 70px;
        }

        .titulo_reporte {
            position: relative;
            left: 1px;
            font-weight: bold;
            font-size: 25px;
            margin-top: 23px
        }

        .logo_reporte {
            position: absolute;
            right: 10px;
            top: 5px;
            width: 70px;
            height: 70px;
        }

        .footer {
            border-top: 1px solid black;
            font-size: 13px;
        }

        .footer p:first-child {
            margin-bottom: 10px;
        }

        .tabla_informacion {
            width: 540px;
            margin: auto;
            height: 75px;
        }

        .tabla_informacion_pequeña {
            width: 270px;
            height: 75px;
        }

        .container_tablas_vacas_productoras {
            width: 540px;
            margin: auto;
            position: relative
        }

        .container_tablas_vacas_productoras table:nth-last-child(1) {

            position: absolute;
            top: 0;
            left: 17rem;
        }

        .titulo_tabla th {
            text-align: end;
            font-size: 17px;
            padding: 8px 9px;
            background-color: #af842d;
            color: white;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            /*    padding-right: 29rem; */
            position: relative;
            height: 1rem;
        }


        .celda_sin_info,
        .celda_tabla {
            padding-left: 5px;
        }

        .celda_tabla {
            border-right: 1px solid black;
        }

        .tabla_ventas_leche,
        .tabla_ventas_ganado,
        .tabla_natalidad {
            width: 496px;
            margin: auto
        }

        .tabla_ventas_leche thead,
        .tabla_ventas_ganado thead,
        .tabla_natalidad thead {
            background: rgb(0, 0, 0, .10);

        }

        .tabla_ventas_leche th,
        .tabla_ventas_leche td,
        .tabla_ventas_ganado th,
        .tabla_ventas_ganado td,
        .tabla_natalidad th,
        .tabla_natalidad td {
            height: 20px;
            font-size: 12px;
            border: 1px solid #B9B9B9;
            text-align: center;
        }

        .tabla_ventas_ganado th,
        .tabla_ventas_ganado td {
            height: 35px;

        }

        .tabla_ventas_leche th:nth-child(1) {
            border-top-left-radius: 5px;
        }

        .tabla_ventas_leche th:nth-last-child(1) {
            border-top-right-radius: 5px;
        }

        .fila_celdas td:nth-last-child(1) {
            border: none;
        }

        .celda_tabla h4,
        .celda_tabla p {
            font-size: 15px;
            text-align: center
        }

        .celda_tabla h4 {
            margin-bottom: 2px;
        }

        .celda_tabla h4 {
            font-weight: bold;
        }

        /* estilos graficos */
        .contenedor_dos_graficos {
            margin: auto;
            width: 860px;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .contenedor_dos_graficos :nth-child(1) {
            margin-right: 10px;
        }

        .container_grafico_torta {
            width: 300px;
            margin: auto;
        }

        .container_grafico_lineal,
        .container_grafico_barra {
            margin: auto;
        }

        .container_grafico_lineal,
        .container_grafico_barra {

            display: inline-block;
        }
    </style>
</head>

<body>
    <header class="header">

        <h3 class="titulo_reporte">{{ $tituloReporte }}</h3>

        <img class="logo_reporte" src="{{ public_path('logo-light-fuente.png') }}" alt="hd" width="70px"
            height="70px">

    </header>

    {{ $slot }}

    <footer class="footer">
            <p> <span style="font-style: italic ">Reporte hacienda </span>

                <b>{{ $nombreHacienda }}</b>

            </p>

            <p>    <span style="font-style: italic"> Fecha: </span>
                        @php
                        $fechaActual = new DateTime();
                        echo $fechaActual->format('d-m-Y');
                    @endphp

                </p>


    </footer>
</body>

</html>
