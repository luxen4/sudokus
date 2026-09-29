<?php

// ============================================================
// GENERAR PDF CON 3 SUDOKUS
// ============================================================

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// ============================================================
// FUNCIONES
// ============================================================

/**
 * Comprueba si un tablero es válido.
 */
function tableroValido(array $tablero): bool
{
    // --------------------------------------------------------
    // FILAS
    // --------------------------------------------------------

    for ($fila = 0; $fila < 9; $fila++) {

        $vistos = [];

        for ($columna = 0; $columna < 9; $columna++) {

            $numero = $tablero[$fila][$columna];

            if ($numero === 0) {
                continue;
            }

            if (isset($vistos[$numero])) {
                return false;
            }

            $vistos[$numero] = true;
        }
    }


    // --------------------------------------------------------
    // COLUMNAS
    // --------------------------------------------------------

    for ($columna = 0; $columna < 9; $columna++) {

        $vistos = [];

        for ($fila = 0; $fila < 9; $fila++) {

            $numero = $tablero[$fila][$columna];

            if ($numero === 0) {
                continue;
            }

            if (isset($vistos[$numero])) {
                return false;
            }

            $vistos[$numero] = true;
        }
    }


    // --------------------------------------------------------
    // BLOQUES 3x3
    // --------------------------------------------------------

    for ($inicioFila = 0; $inicioFila < 9; $inicioFila += 3) {

        for ($inicioColumna = 0; $inicioColumna < 9; $inicioColumna += 3) {

            $vistos = [];

            for ($fila = $inicioFila; $fila < $inicioFila + 3; $fila++) {

                for ($columna = $inicioColumna; $columna < $inicioColumna + 3; $columna++) {

                    $numero = $tablero[$fila][$columna];

                    if ($numero === 0) {
                        continue;
                    }

                    if (isset($vistos[$numero])) {
                        return false;
                    }

                    $vistos[$numero] = true;
                }
            }
        }
    }

    return true;
}


/**
 * Convierte la cadena de 81 caracteres del CSV
 * en un tablero 9x9.
 */
function convertirTablero(string $cadena): array
{
    $tablero = [];

    for ($fila = 0; $fila < 9; $fila++) {

        $tablero[$fila] = [];

        for ($columna = 0; $columna < 9; $columna++) {

            $posicion = ($fila * 9) + $columna;

            $caracter = $cadena[$posicion];

            if ($caracter === '*') {

                $tablero[$fila][$columna] = 0;

            } else {

                $tablero[$fila][$columna] = (int) $caracter;
            }
        }
    }

    return $tablero;
}


/**
 * Lee todos los sudokus disponibles del CSV.
 */
function cargarSudokus(string $archivoCSV): array
{
    $sudokus = [];

    if (!file_exists($archivoCSV)) {
        die('No existe el archivo sudokus.csv');
    }

    $handle = fopen($archivoCSV, 'r');

    if ($handle === false) {
        die('No se puede abrir sudokus.csv');
    }

    // Saltar cabecera
    fgetcsv($handle);

    while (($fila = fgetcsv($handle)) !== false) {

        if (count($fila) < 3) {
            continue;
        }

        $id = trim(
            $fila[0],
            " \t\n\r\0\x0B'"
        );

        $dificultad = trim(
            $fila[1],
            " \t\n\r\0\x0B'"
        );

        $cadena = trim(
            $fila[2],
            " \t\n\r\0\x0B'"
        );


        // Debe tener exactamente 81 caracteres
        if (strlen($cadena) !== 81) {
            continue;
        }


        // Solo * y números 1-9
        if (!preg_match('/^[*1-9]{81}$/', $cadena)) {
            continue;
        }


        $tablero = convertirTablero($cadena);


        // El tablero inicial no debe tener conflictos
        if (!tableroValido($tablero)) {
            continue;
        }


        $sudokus[] = [
            'id' => $id,
            'dificultad' => $dificultad,
            'cadena' => $cadena,
            'tablero' => $tablero
        ];
    }

    fclose($handle);

    return $sudokus;
}


/**
 * Dibuja un Sudoku en HTML.
 */
function dibujarSudoku(array $tablero, int $numero, string $dificultad): string
{
    $html = '';

    $html .= '<div class="sudoku-contenedor">';

    $html .= '<div class="titulo-sudoku">';
    $html .= 'SUDOKU ' . $numero;
    $html .= ' - Dificultad: '; $html .= htmlspecialchars($dificultad);
    $html .= '</div>';

    $html .= '<table class="sudoku">';


    for ($fila = 0; $fila < 9; $fila++) {

        $html .= '<tr>';

        for ($columna = 0; $columna < 9; $columna++) {

            $valor = $tablero[$fila][$columna];


            // Clases para los bloques 3x3

            $clases = [];

            if (($columna + 1) % 3 === 0 && $columna !== 8) {
                $clases[] = 'borde-derecho';
            }

            if (($fila + 1) % 3 === 0 && $fila !== 8) {
                $clases[] = 'borde-abajo';
            }


            $clase = implode(' ', $clases);


            $html .= '<td class="' . $clase . '">';


            if ($valor !== 0) {

                $html .= htmlspecialchars(
                    (string) $valor
                );
            }


            $html .= '</td>';
        }

        $html .= '</tr>';
    }


    $html .= '</table>';

    $html .= '</div>';


    return $html;
}


// ============================================================
// CARGAR SUDOKUS
// ============================================================

$archivoCSV = $_SERVER['DOCUMENT_ROOT'] . '/sudokus/data/sudokus.csv';

$sudokus = cargarSudokus($archivoCSV);


if (count($sudokus) < 3) {

    die(
        'Necesitas al menos 3 sudokus válidos en sudokus.csv.'
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

@page {
    size: 165mm 235mm;
    margin: 10mm;
}


body {

    margin: 0;

    padding: 0;

    font-family:
        DejaVu Sans,
        Arial,
        sans-serif;

    color: #111;

}


.cabecera {

    text-align: center;

    margin-bottom: 5mm;

}


.cabecera h1 {

    margin: 0;

    font-size: 20px;

    letter-spacing: 1px;

}


.cabecera p {

    margin: 2px 0 0;

    font-size: 9px;

    color: #555;

}


.sudoku-contenedor {

    text-align: center;

    margin-bottom: 5mm;

    page-break-inside: avoid;

}


.titulo-sudoku {

    font-size: 11px;

    font-weight: bold;

    margin-bottom: 1.5mm;

    text-align: center;

}


.sudoku {

    border-collapse: collapse;

    margin: 0 auto;

}


.sudoku td {

    width: 9.5mm;

    height: 9.5mm;

    padding: 0;

    border: 0.5px solid #777;

    text-align: center;

    vertical-align: middle;

    font-size: 14px;

    font-weight: normal;

}


.sudoku td.borde-derecho {

    border-right: 2px solid #111;

}


.sudoku td.borde-abajo {

    border-bottom: 2px solid #111;

}


.sudoku tr:first-child td {

    border-top: 2px solid #111;

}


.sudoku td:first-child {

    border-left: 2px solid #111;

}


.sudoku tr:last-child td {

    border-bottom: 2px solid #111;

}


.sudoku td:last-child {

    border-right: 2px solid #111;

}


.pie {

    text-align: center;

    margin-top: 2mm;

    font-size: 8px;

    color: #777;

}

</style>

</head>

<body>

<div class="cabecera">

   <!-- <h1>PASATIEMPOS · SUDOKU</h1>

    <p>Completa los tres sudokus</p>-->

</div>

HTML;

// ============================================================
// AÑADIR TODOS LOS SUDOKUS
// 2 SUDOKUS POR PÁGINA
// ============================================================

$numero = 1;

$totalSudokus = count($sudokus);

foreach ($sudokus as $indice => $sudoku) {

    // --------------------------------------------------------
    // Cada 2 sudokus comenzamos una nueva página
    // excepto antes del primero
    // --------------------------------------------------------

    if ($numero > 1 && ($numero - 1) % 2 === 0) {

        $html .= '<div class="salto-pagina"></div>';
    }


    // --------------------------------------------------------
    // Dibujar Sudoku
    // --------------------------------------------------------

    $html .= dibujarSudoku(
        $sudoku['tablero'],
        $numero,
        $sudoku['dificultad']
    );


    $numero++;
}


$html .= <<<HTML

<div class="pie">

    ¡Buena suerte!

</div>

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


$dompdf = new Dompdf($options);


// Cargar HTML
$dompdf->loadHtml($html);


// A4 vertical
$dompdf->setPaper(
    'A4',
    'portrait'
);


// Generar PDF
$dompdf->render();


// Mostrar en navegador
$dompdf->stream(
    'pasatiempos-sudoku.pdf',
    [
        'Attachment' => false
    ]
);

exit;
?>
