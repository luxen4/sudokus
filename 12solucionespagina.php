<?php

// ============================================================
// GENERAR PDF - 12 SOLUCIONES POR PÁGINA
// Formato: 165 x 235 mm
// Distribución: 3 columnas x 4 filas
// ============================================================

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// ============================================================
// CONFIGURACIÓN
// ============================================================

$archivoCSV = __DIR__ . '/sudokus.csv';


// ============================================================
// CONVERTIR SOLUCIÓN A TABLERO 9x9
// ============================================================

function convertirSolucion(string $cadena): array
{
    $cadena = trim($cadena);

    if (
        strlen($cadena) !== 81 ||
        !preg_match('/^[1-9]{81}$/', $cadena)
    ) {
        return [];
    }

    $tablero = [];

    for ($fila = 0; $fila < 9; $fila++) {

        $tablero[$fila] = [];

        for ($columna = 0; $columna < 9; $columna++) {

            $posicion = ($fila * 9) + $columna;

            $tablero[$fila][$columna] =
                (int) $cadena[$posicion];
        }
    }

    return $tablero;
}


// ============================================================
// LEER SUDOKUS
// ============================================================

function cargarSudokus(string $archivo): array
{
    $sudokus = [];

    if (!file_exists($archivo)) {

        die(
            'No existe el archivo sudokus.csv'
        );
    }

    $handle = fopen($archivo, 'r');

    if ($handle === false) {

        die(
            'No se puede abrir sudokus.csv'
        );
    }


    // --------------------------------------------------------
    // CABECERA
    // --------------------------------------------------------

    $cabecera = fgetcsv($handle);

    if ($cabecera === false) {

        fclose($handle);

        die(
            'El archivo sudokus.csv está vacío.'
        );
    }


    // --------------------------------------------------------
    // BUSCAR POSICIONES DE LAS COLUMNAS
    // --------------------------------------------------------

    $indiceId = array_search(
        'id',
        $cabecera
    );

    $indiceDificultad = array_search(
        'dificultad',
        $cabecera
    );

    $indiceSolucion = array_search(
        'solucion',
        $cabecera
    );


    if (
        $indiceId === false ||
        $indiceDificultad === false ||
        $indiceSolucion === false
    ) {

        fclose($handle);

        die(
            'sudokus.csv debe contener las columnas: id,dificultad,tablero,solucion'
        );
    }


    // --------------------------------------------------------
    // RECORRER TODO EL CSV
    // --------------------------------------------------------

    while (
        ($fila = fgetcsv($handle)) !== false
    ) {

        if (
            !isset(
                $fila[$indiceId],
                $fila[$indiceDificultad],
                $fila[$indiceSolucion]
            )
        ) {
            continue;
        }


        $id = trim(
            $fila[$indiceId],
            " \t\n\r\0\x0B'"
        );

        $dificultad = trim(
            $fila[$indiceDificultad],
            " \t\n\r\0\x0B'"
        );

        $solucion = trim(
            $fila[$indiceSolucion],
            " \t\n\r\0\x0B'"
        );


        // ----------------------------------------------------
        // IGNORAR SOLUCIONES VACÍAS
        // ----------------------------------------------------

        if ($solucion === '') {
            continue;
        }


        // ----------------------------------------------------
        // CONVERTIR SOLUCIÓN
        // ----------------------------------------------------

        $tablero = convertirSolucion(
            $solucion
        );


        if (empty($tablero)) {
            continue;
        }


        $sudokus[] = [
            'id' => $id,
            'dificultad' => $dificultad,
            'solucion' => $solucion,
            'tablero' => $tablero
        ];
    }


    fclose($handle);

    return $sudokus;
}


// ============================================================
// DIBUJAR UNA SOLUCIÓN
// ============================================================

function dibujarSolucion(
    array $sudoku
): string {

    $html = '';

    $html .= '<div class="solucion-contenedor">';


    // --------------------------------------------------------
    // TABLERO
    // --------------------------------------------------------

    $html .= '<table class="sudoku">';


    for ($fila = 0; $fila < 9; $fila++) {

        $html .= '<tr>';


        for ($columna = 0; $columna < 9; $columna++) {

            $valor =
                $sudoku['tablero'][$fila][$columna];


            $clases = [];


            // Bloques 3x3

            if (
                ($columna + 1) % 3 === 0 &&
                $columna !== 8
            ) {

                $clases[] =
                    'borde-derecho';
            }


            if (
                ($fila + 1) % 3 === 0 &&
                $fila !== 8
            ) {

                $clases[] =
                    'borde-abajo';
            }


            $clase =
                implode(
                    ' ',
                    $clases
                );


            $html .=
                '<td class="' .
                $clase .
                '">' .
                $valor .
                '</td>';
        }


        $html .= '</tr>';
    }


    $html .= '</table>';


    // --------------------------------------------------------
    // NÚMERO DE SUDOKU DEBAJO
    // --------------------------------------------------------

    $html .=
        '<div class="numero-sudoku">' .
        'SUDOKU ' .
        htmlspecialchars(
            $sudoku['id']
        ) .
        '</div>';


    $html .= '</div>';


    return $html;
}


// ============================================================
// CARGAR TODOS LOS SUDOKUS
// ============================================================

$sudokus = cargarSudokus(
    $archivoCSV
);


if (empty($sudokus)) {

    die(
        'No hay soluciones válidas en sudokus.csv.'
    );
}


// ============================================================
// CREAR HTML
// ============================================================

$html = <<<HTML
<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<style>

/* ============================================================
   PÁGINA
   ============================================================ */

@page {
    size: 165mm 235mm;
    margin: 7mm;
}


/* ============================================================
   GENERAL
   ============================================================ */

html,
body {

    margin: 0;
    padding: 0;

    font-family:
        DejaVu Sans,
        Arial,
        sans-serif;

    color: #111;

}


/* ============================================================
   PÁGINA DE SUDOKUS
   ============================================================ */

.pagina {

    width: 151mm;
    height: 221mm;

    display: table;

    table-layout: fixed;

}


/* ============================================================
   FILA
   ============================================================ */

.fila {

    display: table-row;

}


/* ============================================================
   CELDA
   ============================================================ */

.celda {

    display: table-cell;

    width: 33.33%;

    height: 55mm;

    vertical-align: middle;

    text-align: center;

    padding: 1mm;

}


/* ============================================================
   CONTENEDOR DEL SUDOKU
   ============================================================ */

.solucion-contenedor {

    display: inline-block;

    text-align: center;

}


/* ============================================================
   TABLERO
   ============================================================ */

.sudoku {

    border-collapse: collapse;

    margin: 0 auto;

}


/* ============================================================
   CASILLAS
   ============================================================ */

.sudoku td {

    width: 4.7mm;

    height: 4.7mm;

    padding: 0;

    border:
        0.35px solid #777;

    text-align: center;

    vertical-align: middle;

    font-size: 8px;

    line-height: 1;

}


/* ============================================================
   BLOQUES 3x3
   ============================================================ */

.sudoku td.borde-derecho {

    border-right:
        1.3px solid #111;

}


.sudoku td.borde-abajo {

    border-bottom:
        1.3px solid #111;

}


.sudoku tr:first-child td {

    border-top:
        1.3px solid #111;

}


.sudoku td:first-child {

    border-left:
        1.3px solid #111;

}


.sudoku tr:last-child td {

    border-bottom:
        1.3px solid #111;

}


.sudoku td:last-child {

    border-right:
        1.3px solid #111;

}


/* ============================================================
   NÚMERO DEL SUDOKU
   ============================================================ */

.numero-sudoku {

    margin-top: 1.5mm;

    font-size: 8px;

    font-weight: bold;

    text-align: center;

}


/* ============================================================
   SALTO DE PÁGINA
   ============================================================ */

.nueva-pagina {

    page-break-before: always;

}


</style>

</head>

<body>

HTML;


// ============================================================
// 12 SUDOKUS POR PÁGINA
// ============================================================

$total = count($sudokus);

for (
    $i = 0;
    $i < $total;
    $i++
) {


    // --------------------------------------------------------
    // COMIENZO DE UNA NUEVA PÁGINA
    // --------------------------------------------------------

    if ($i % 12 === 0) {

        if ($i > 0) {

            $html .=
                '</div>';
        }


        $html .=
            '<div class="pagina">';
    }


    // --------------------------------------------------------
    // COMIENZO DE FILA
    // --------------------------------------------------------

    if ($i % 3 === 0) {

        $html .=
            '<div class="fila">';
    }


    // --------------------------------------------------------
    // SUDOKU
    // --------------------------------------------------------

    $html .=
        '<div class="celda">';

    $html .=
        dibujarSolucion(
            $sudokus[$i]
        );

    $html .=
        '</div>';


    // --------------------------------------------------------
    // FINAL DE FILA
    // --------------------------------------------------------

    if (
        $i % 3 === 2 ||
        $i === $total - 1
    ) {

        $html .=
            '</div>';
    }


    // --------------------------------------------------------
    // FINAL DE PÁGINA
    // --------------------------------------------------------

    if (
        $i % 12 === 11 ||
        $i === $total - 1
    ) {

        $html .=
            '</div>';
    }
}


$html .= <<<HTML

</body>

</html>

HTML;


// ============================================================
// CREAR PDF
// ============================================================

$options = new Options();

$options->set(
    'defaultFont',
    'DejaVu Sans'
);


$dompdf =
    new Dompdf(
        $options
    );


// ------------------------------------------------------------
// CARGAR HTML
// ------------------------------------------------------------

$dompdf->loadHtml(
    $html
);


// ------------------------------------------------------------
// TAMAÑO EXACTO DEL LIBRO
// ------------------------------------------------------------

$dompdf->setPaper(
    [
        0,
        0,
        165 * 2.83465,
        235 * 2.83465
    ]
);


// ------------------------------------------------------------
// GENERAR
// ------------------------------------------------------------

$dompdf->render();


// ------------------------------------------------------------
// MOSTRAR PDF
// ------------------------------------------------------------

$dompdf->stream(
    'soluciones-sudoku.pdf',
    [
        'Attachment' => false
    ]
);

exit;

?>
