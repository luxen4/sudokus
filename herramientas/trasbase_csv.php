<?php

// ============================================================
// TRASBASE CSV
//
// partidas.csv
//      ↓
// busca la última partida correcta
//      ↓
// utiliza el campo "tablero"
//      ↓
// convierte los 0 en *
//      ↓
// genera el nuevo SUxxx
//      ↓
// añade UNA SOLA LÍNEA a sudokus.csv
// ============================================================


$archivoPartidas = __DIR__ . '/../data/partidas.csv'; 

$archivoSudokus = __DIR__ . '/../data/sudokus.csv';


// ============================================================
// CONVERTIR TABLERO
// ============================================================
//
// En partidas.csv:
//
// 0 = casilla vacía
// 1-9 = número
//
// En sudokus.csv:
//
// * = casilla vacía
// 1-9 = número
//
// ============================================================

function convertirTablero(string $tablero): string
{
    $tablero = trim($tablero);


    // --------------------------------------------------------
    // Comprobar que tiene exactamente 81 posiciones
    // --------------------------------------------------------

    if (
        strlen($tablero) !== 81 ||
        !preg_match('/^[0-9]{81}$/', $tablero)
    ) {

        die(
            'El campo tablero no contiene exactamente 81 números.'
        );
    }


    $resultado = '';


    // --------------------------------------------------------
    // Convertir 0 → *
    // --------------------------------------------------------

    for ($i = 0; $i < 81; $i++) {

        $numero = $tablero[$i];


        if ($numero === '0') {

            $resultado .= '*';

        } else {

            $resultado .= $numero;
        }
    }


    return $resultado;
}


// ============================================================
// OBTENER SIGUIENTE ID
// ============================================================

function obtenerSiguienteId(string $archivo): string
{
    $ultimoId = 0;


    // --------------------------------------------------------
    // Si el archivo no existe
    // --------------------------------------------------------

    if (!file_exists($archivo)) {

        return 'SU001';
    }


    // --------------------------------------------------------
    // Abrir archivo
    // --------------------------------------------------------

    $handle = fopen(
        $archivo,
        'r'
    );


    if ($handle === false) {

        return 'SU001';
    }


    // --------------------------------------------------------
    // Saltar cabecera
    // --------------------------------------------------------

    fgetcsv($handle);


    // --------------------------------------------------------
    // Leer registros
    // --------------------------------------------------------

    while (
        ($fila = fgetcsv($handle)) !== false
    ) {

        if (count($fila) < 1) {

            continue;
        }


        $id = trim(
            $fila[0],
            " \t\n\r\0\x0B'"
        );


        // ----------------------------------------------------
        // Buscar IDs del tipo SU001, SU002, SU003...
        // ----------------------------------------------------

        if (
            preg_match(
                '/^SU(\d+)$/',
                $id,
                $coincidencia
            )
        ) {

            $numero =
                (int) $coincidencia[1];


            if ($numero > $ultimoId) {

                $ultimoId =
                    $numero;
            }
        }
    }


    fclose($handle);


    // --------------------------------------------------------
    // Generar siguiente ID
    //
    // 1    → SU001
    // 9    → SU009
    // 19   → SU019
    // 100  → SU100
    // --------------------------------------------------------

    return 'SU' .
        str_pad(
            $ultimoId + 1,
            3,
            '0',
            STR_PAD_LEFT
        );
}


// ============================================================
// COMPROBAR ARCHIVOS
// ============================================================

if (!file_exists($archivoPartidas)) {

    die(
        'No existe partidas.csv'
    );
}


if (!file_exists($archivoSudokus)) {

    die(
        'No existe sudokus.csv'
    );
}


// ============================================================
// LEER PARTIDAS.CSV
// ============================================================

$handle = fopen(
    $archivoPartidas,
    'r'
);


if ($handle === false) {

    die(
        'No se puede abrir partidas.csv'
    );
}


// ------------------------------------------------------------
// Saltar cabecera
// ------------------------------------------------------------

fgetcsv($handle);


$partidaCorrecta = null;


// ============================================================
// BUSCAR ÚLTIMA PARTIDA CORRECTA
// ============================================================

while (
    ($fila = fgetcsv($handle)) !== false
) {

    // Necesitamos:

    // 0 = id_partida
    // 1 = fecha
    // 2 = dificultad
    // 3 = tablero
    // 4 = solucion
    // 5 = tablero_final
    // 6 = resultado

    if (count($fila) < 7) {

        continue;
    }


    $resultado =
        strtolower(
            trim($fila[6])
        );


    // --------------------------------------------------------
    // Solo nos interesan partidas correctas
    // --------------------------------------------------------

    if ($resultado === 'correcto') {

        $partidaCorrecta = [

            'id_partida' =>
                trim($fila[0]),

            'fecha' =>
                trim($fila[1]),

            'dificultad' =>
                trim($fila[2]),

            'tablero' =>
                trim($fila[3]),

            'solucion' =>
                trim($fila[4]),

            'tablero_final' =>
                trim($fila[5]),

            'resultado' =>
                trim($fila[6])
        ];
    }
}


fclose($handle);


// ============================================================
// COMPROBAR QUE EXISTE UNA PARTIDA CORRECTA
// ============================================================

if ($partidaCorrecta === null) {

    die(
        'No existe ninguna partida con resultado correcto.'
    );
}


// ============================================================
// UTILIZAR SIEMPRE EL CAMPO "TABLERO"
// ============================================================

$tableroOriginal =
    $partidaCorrecta['tablero'];


// ============================================================
// CONVERTIR 0 → *
// ============================================================

$tableroNuevo =
    convertirTablero(
        $tableroOriginal
    );


// ============================================================
// OBTENER NUEVO ID
// ============================================================

$nuevoId =
    obtenerSiguienteId(
        $archivoSudokus
    );


// ============================================================
// DIFICULTAD
// ============================================================

$dificultad =
    $partidaCorrecta['dificultad'];



// ============================================================
// SOLUCIÓN
// ============================================================

$solucion =
    $partidaCorrecta['solucion'];

// ============================================================
// PREPARAR UNA ÚNICA LÍNEA
// ============================================================
//
// Resultado:
//
// 'SU020','Fácil','***035***093106*5*...'
//
// ============================================================

$nuevaLinea =
    "'" .
    str_replace(
        "'",
        "''",
        $nuevoId
    ) .
    "','" .
    str_replace(
        "'",
        "''",
        $dificultad
    ) .
    "','" .
    str_replace(
        "'",
        "''",
        $tableroNuevo
    ) .
    "','" .
    str_replace(
        "'",
        "''",
        $solucion
    ) .
    "'";


// ============================================================
// LEER CONTENIDO ACTUAL DE SUDOKUS.CSV
// ============================================================

$contenido =
    file_get_contents(
        $archivoSudokus
    );


if ($contenido === false) {

    die(
        'No se puede leer sudokus.csv'
    );
}


// ============================================================
// COMPROBAR QUE EL ID NO EXISTE
// ============================================================

$patronId =
    "/^'" .
    preg_quote(
        $nuevoId,
        '/'
    ) .
    "'/m";


if (
    preg_match(
        $patronId,
        $contenido
    )
) {

    die(
        'El Sudoku ' .
        htmlspecialchars(
            $nuevoId
        ) .
        ' ya existe en sudokus.csv.'
    );
}


// ============================================================
// COMPROBAR SI NECESITAMOS SALTO DE LÍNEA
// ============================================================
//
// Si el archivo ya termina en:
//
// \n
//
// no añadimos otro.
//
// Si NO termina en salto de línea,
// añadimos uno antes del nuevo registro.
//
// ============================================================

$prefijo = '';


if (
    $contenido !== '' &&
    !preg_match(
        "/\r?\n$/",
        $contenido
    )
) {

    $prefijo =
        PHP_EOL;
}


// ============================================================
// ESCRIBIR UNA SOLA VEZ
// ============================================================

$handle = fopen(
    $archivoSudokus,
    'a'
);


if ($handle === false) {

    die(
        'No se puede abrir sudokus.csv para escribir.'
    );
}


// ------------------------------------------------------------
// UNA SOLA ESCRITURA
// ------------------------------------------------------------

$bytesEscritos =
    fwrite(
        $handle,
        $prefijo . $nuevaLinea
    );


fclose($handle);


// ============================================================
// COMPROBAR ESCRITURA
// ============================================================

if ($bytesEscritos === false) {

    die(
        'No se pudo escribir el nuevo Sudoku.'
    );
}


// ============================================================
// RESULTADO
// ============================================================

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Sudoku añadido</title>


<style>

body {

    margin: 0;

    padding: 40px 20px;

    background: #f3f4f6;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    text-align: center;

    color: #111827;
}


.contenedor {

    max-width: 700px;

    margin: auto;

    padding: 30px;

    background: white;

    border-radius: 12px;

    box-shadow:
        0 5px 20px
        rgba(0, 0, 0, 0.08);
}


.correcto {

    padding: 15px;

    margin-bottom: 20px;

    border:
        1px solid
        #86efac;

    border-radius: 8px;

    background: #dcfce7;

    color: #166534;
}


.codigo {

    margin-top: 20px;

    padding: 15px;

    background: #f3f4f6;

    border-radius: 8px;

    font-family: monospace;

    font-size: 14px;

    text-align: left;

    overflow-wrap: anywhere;
}


button {

    margin-top: 20px;

    padding: 12px 20px;

    border: 0;

    border-radius: 7px;

    background: #2563eb;

    color: white;

    font-size: 16px;

    cursor: pointer;
}


button:hover {

    background: #1d4ed8;
}

</style>

</head>


<body>


<div class="contenedor">

    <h1>🎉 Sudoku añadido</h1>


    <div class="correcto">

        El Sudoku se ha añadido correctamente
        a <strong>sudokus.csv</strong>.

    </div>


    <p>

        <strong>ID:</strong>

        <?= htmlspecialchars($nuevoId) ?>

    </p>


    <p>

        <strong>Dificultad:</strong>

        <?= htmlspecialchars($dificultad) ?>

    </p>


    <p>

        <strong>Partida utilizada:</strong>

        <?= htmlspecialchars(
            $partidaCorrecta['id_partida']
        ) ?>

    </p>


    <div class="codigo">

        <?= htmlspecialchars($nuevaLinea) ?>

    </div>


    <button
        onclick="window.location.href='indexGenerador.php'"
    >

        Volver al Sudoku

    </button>

</div>


</body>

</html>