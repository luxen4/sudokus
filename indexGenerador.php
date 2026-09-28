<?php 
// ============================================================ 
// CONFIGURACIÓN 
// ============================================================ 
$archivoPartidas = __DIR__ . '/partidas.csv'; 
// ============================================================ 
// GENERAR TABLERO VACÍO 
// ============================================================ 
function tableroVacio(): array { return array_fill( 0, 9, array_fill(0, 9, 0) ); } 
// ============================================================ 
// COMPROBAR SI SE PUEDE PONER UN NÚMERO 
// ============================================================ 
function sePuedePoner( array &$tablero, int $fila, int $columna, int $numero ): bool { 
// Fila 
for ($c = 0; $c < 9; $c++) { if ($tablero[$fila][$c] === $numero) { return false; } } 
// Columna 
for ($f = 0; $f < 9; $f++) { if ($tablero[$f][$columna] === $numero) { return false; } } 
// Bloque 3x3 
$inicioFila = intdiv($fila, 3) * 3; $inicioColumna = intdiv($columna, 3) * 3; 
for ( $f = $inicioFila; $f < $inicioFila + 3; $f++ ) { 
    for ( $c = $inicioColumna; $c < $inicioColumna + 3; $c++ ) { 
        if ($tablero[$f][$c] === $numero) { return false; } } } return true; } 
// ============================================================ 
// GENERAR SOLUCIÓN COMPLETA 
// ============================================================ 

function generarSolucion(array &$tablero): bool { 
// Buscar primera casilla vacía 
for ($fila = 0; $fila < 9; $fila++) { 
    for ($columna = 0; $columna < 9; $columna++) { if ($tablero[$fila][$columna] !== 0) { continue; } $numeros = range(1, 9); 
// Mezclar números para que cada Sudoku 
// sea diferente 
shuffle($numeros); foreach ($numeros as $numero) { if ( !sePuedePoner( $tablero, $fila, $columna, $numero ) ) { continue; } $tablero[$fila][$columna] = $numero; if ( generarSolucion($tablero) ) { return true; } $tablero[$fila][$columna] = 0; } return false; } } return true; } 
// ============================================================ 
// CONTAR SOLUCIONES 
// 
// Se detiene al encontrar más de una. 
// Esto nos permite garantizar que el Sudoku 
// generado tiene una única solución. 
// ============================================================ 
function contarSoluciones( array &$tablero, int &$soluciones, int $limite = 2 ): void { if ($soluciones >= $limite) { return; } 
// Buscar la casilla vacía con menos posibilidades. 
// Esto hace mucho más rápida la comprobación. 
$mejorFila = -1; $mejorColumna = -1; $mejoresNumeros = null; for ($fila = 0; $fila < 9; $fila++) { for ($columna = 0; $columna < 9; $columna++) { if ($tablero[$fila][$columna] !== 0) { continue; } $posibles = []; for ($numero = 1; $numero <= 9; $numero++) { if ( sePuedePoner( $tablero, $fila, $columna, $numero ) ) { $posibles[] = $numero; } } 
// No existe ninguna posibilidad 
if (empty($posibles)) { return; } if ( $mejoresNumeros === null || count($posibles) < count($mejoresNumeros) ) { $mejorFila = $fila; $mejorColumna = $columna; $mejoresNumeros = $posibles; if (count($posibles) === 1) { break 2; } } } } 
// No quedan casillas vacías. 
// Hemos encontrado una solución. 
if ($mejorFila === -1) { $soluciones++; return; } 
foreach ($mejoresNumeros as $numero) { $tablero[$mejorFila][$mejorColumna] = $numero; contarSoluciones( $tablero, $soluciones, $limite ); $tablero[$mejorFila][$mejorColumna] = 0; if ($soluciones >= $limite) { return; } } } 
// ============================================================ 
// COMPROBAR SI TIENE UNA ÚNICA SOLUCIÓN 
// ============================================================ 
function tieneUnaUnicaSolucion(array $tablero): bool { $soluciones = 0; contarSoluciones( $tablero, $soluciones, 2 ); return $soluciones === 1; } 




// ============================================================ 
// CREAR SUDOKU 
// 
// Dificultad: 
    // Fácil -> 38-43 huecos 
    // Media -> 48-53 huecos 
    // Difícil -> 54-59 huecos 
    // ============================================================ 
    function generarSudoku( string $dificultad ): array { $huecosMinimos = 38; $huecosMaximos = 43; if ($dificultad === 'Difícil') { $huecosMinimos = 54; $huecosMaximos = 59; } elseif ($dificultad === 'Media') { $huecosMinimos = 48; $huecosMaximos = 53; } 
    // Generar solución completa 
    $solucion = tableroVacio(); generarSolucion($solucion); 
    // Intentaremos quitar números hasta 
    // alcanzar la dificultad solicitada. 
    $tablero = $solucion; $posiciones = []; for ($fila = 0; $fila < 9; $fila++) { for ($columna = 0; $columna < 9; $columna++) { $posiciones[] = [ $fila, $columna ]; } } shuffle($posiciones); $huecosObjetivo = random_int( $huecosMinimos, $huecosMaximos ); $huecos = 0; foreach ($posiciones as $posicion) { if ($huecos >= $huecosObjetivo) { break; } $fila = $posicion[0]; $columna = $posicion[1]; $valorOriginal = $tablero[$fila][$columna]; $tablero[$fila][$columna] = 0; 
    // Comprobar que sigue teniendo 
    // exactamente una solución. 
    if ( tieneUnaUnicaSolucion($tablero) ) { $huecos++; } else { 
    // Si al quitarlo aparecen varias 
    // soluciones, lo volvemos a poner. 
    $tablero[$fila][$columna] = $valorOriginal; } } return [ 'tablero' => $tablero, 'solucion' => $solucion, 'dificultad' => $dificultad ]; } 
    
    
    
    // ============================================================ 
    // TABLERO VÁLIDO 
    // ============================================================ 
    function tableroValido(array $tablero): bool { 
    // Filas 
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



    // Columnas 
    for ($columna = 0; $columna < 9; $columna++) { $vistos = []; for ($fila = 0; $fila < 9; $fila++) { $numero = $tablero[$fila][$columna]; if ($numero === 0) { continue; } if (isset($vistos[$numero])) { return false; } $vistos[$numero] = true; } } 
    // Bloques 
    for ( $inicioFila = 0; $inicioFila < 9; $inicioFila += 3 ) { for ( $inicioColumna = 0; $inicioColumna < 9; $inicioColumna += 3 ) { $vistos = []; for ( $fila = $inicioFila; $fila < $inicioFila + 3; $fila++ ) { for ( $columna = $inicioColumna; $columna < $inicioColumna + 3; $columna++ ) { $numero = $tablero[$fila][$columna]; if ($numero === 0) { continue; } if (isset($vistos[$numero])) { return false; } $vistos[$numero] = true; } } } } return true; 
    
    
    // ============================================================ 
    // TABLERO COMPLETO 
    // ============================================================ 
    }
    function tableroCompleto(array $tablero): bool { for ($fila = 0; $fila < 9; $fila++) { for ($columna = 0; $columna < 9; $columna++) { if ($tablero[$fila][$columna] === 0) { return false; } } } return true; } 
    // ============================================================ 
    // MATRIZ -> CADENA 
    // ============================================================ 
    function tableroACadena(array $tablero): string { $cadena = ''; 
    for ($fila = 0; $fila < 9; $fila++) { for ($columna = 0; $columna < 9; $columna++) { $cadena .= (string)$tablero[$fila][$columna]; } } return $cadena; } 
    // ============================================================ 
    // GENERAR ID 
    // ============================================================ 
    function generarIdPartida(): string { return 'PART-' . date('Ymd-His') . '-' . strtoupper( bin2hex(random_bytes(3)) ); } 



    

    // ============================================================ 
    // GENERAR NUEVO SUDOKU 
    // 
    // Si se ha enviado POST, primero necesitamos mantener 
    // el Sudoku actual. Para ello lo guardamos en sesión. 
    // ============================================================ 
    session_start(); 
    // ============================================================ 
    // NUEVO SUDOKU 
    // ============================================================ 
    if ( !isset($_SESSION['sudoku']) || isset($_POST['nuevo']) ) { $dificultades = [ 'Fácil', 'Media', 'Difícil' ]; $dificultad = $dificultades[ array_rand($dificultades) ]; $generado = generarSudoku( $dificultad ); $_SESSION['sudoku'] = [ 'tablero' => $generado['tablero'], 'solucion' => $generado['solucion'], 'dificultad' => $generado['dificultad'] ]; } $sudoku = $_SESSION['sudoku']; $tableroInicial = $sudoku['tablero']; 
    // ============================================================ 
    // RESULTADO 
    // ============================================================ 
    $resultado = null; 
    // ============================================================ 
    // TABLERO MOSTRADO 
    // ============================================================ 
    $tablero = $tableroInicial; 
    
    
    // ============================================================ 
    // PROCESAR COMPROBACIÓN 
    // ============================================================ 
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['nuevo']) ) { $jugador = []; for ($fila = 0; $fila < 9; $fila++) { $jugador[$fila] = []; for ($columna = 0; $columna < 9; $columna++) { $nombre = 'c_' . $fila . '_' . $columna; $valor = $_POST[$nombre] ?? ''; 
    // Si la casilla original no estaba vacía, 
    // ignoramos completamente lo enviado 
    // por el navegador. 
    if ( $tableroInicial[$fila][$columna] !== 0 ) { $jugador[$fila][$columna] = $tableroInicial[$fila][$columna]; continue; } if ( is_string($valor) && preg_match( '/^[1-9]$/', $valor ) ) { $jugador[$fila][$columna] = (int)$valor; } else { $jugador[$fila][$columna] = 0; } } } $tablero = $jugador; 
    // ======================================================== 
    // COMPROBACIÓN DEFINITIVA 
    // ======================================================== 
    if (!tableroCompleto($jugador)) { $resultado = 'incompleto'; } elseif (!tableroValido($jugador)) { $resultado = 'incorrecto'; } else { $resultado = 'correcto'; 
    
    // ==================================================== 
    // GUARDAR PARTIDA 
    // ==================================================== 
    $archivoNuevo = !file_exists( $archivoPartidas ); $handle = fopen( $archivoPartidas, 'a' ); if ($handle !== false) { flock( $handle, LOCK_EX ); 
    // ID 
    $idPartida = generarIdPartida(); 
    // Fecha 
    $fecha = date('Y-m-d H:i:s'); 
    // Tablero que vio el usuario 
    $tableroPuzzle = tableroACadena( $tableroInicial ); 
    // Solución 
    $solucion = tableroACadena( $sudoku['solucion'] ); 
    // Tablero terminado 
    $tableroFinal = tableroACadena( $jugador ); if ($archivoNuevo) { fputcsv( $handle, [ 'id_partida', 'fecha', 'dificultad', 'tablero', 'solucion', 'tablero_final', 'resultado' ] ); } fputcsv( $handle, [ $idPartida, $fecha, $sudoku['dificultad'], $tableroPuzzle, $solucion, $tableroFinal, 'correcto' ] ); fflush($handle); flock( $handle, LOCK_UN ); fclose($handle); } 
    // Marcamos la partida como terminada. 
    $_SESSION['terminado'] = true; } } ?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

<title>...</title>   
<link rel="stylesheet" href="./styles.css">


</head>

<body>

<h1>Sudoku</h1>

<div class="info">Dificultad: <strong>
    <?php echo htmlspecialchars( $sudoku['dificultad']);?></strong>

</div>

<?php if ($resultado === 'correcto'): ?>

<div class="mensaje correcto">

    🎉 ¡Sudoku correcto!

    <br>

    La partida se ha guardado correctamente.

</div>

<?php elseif ($resultado === 'incompleto'): ?>

<div class="mensaje incompleto">

    ⚠️ El Sudoku está incompleto.

    <br>

    Rellena todas las casillas.

</div>

<?php elseif ($resultado === 'incorrecto'): ?>

<div class="mensaje incorrecto">

    ❌ El Sudoku no es correcto.

    <br>

    Hay números repetidos en filas,
    columnas o bloques 3×3.

</div>

<?php endif; ?>

<form method="POST" id="sudokuForm">

<table class="sudoku">

<?php for ($fila = 0; $fila < 9; $fila++): ?>

<tr>

<?php for ($columna = 0; $columna < 9; $columna++): ?>

<td>

<?php if ($tableroInicial[$fila][$columna] !== 0): ?>

<!-- NÚMERO GENERADO POR EL SISTEMA -->

<input
    type="text"
    class="fijo celda"
    data-fila="<?= $fila ?>"
    data-columna="<?= $columna ?>"
    value="<?= $tableroInicial[$fila][$columna] ?>"
    readonly
>

<?php else: ?>

<!-- CASILLA DEL USUARIO -->

<input
    type="text"
    class="editable celda"
    name="c_<?= $fila ?>_<?= $columna ?>"
    data-fila="<?= $fila ?>"
    data-columna="<?= $columna ?>"
    maxlength="1"
    inputmode="numeric"
    autocomplete="off"
    value="<?= $tablero[$fila][$columna] !== 0
        ? $tablero[$fila][$columna]
        : '' ?>"
>

<?php endif; ?>

</td>

<?php endfor; ?>

</tr>

<?php endfor; ?>

</table>

<div class="botones">

<!-- ======================================================== BOTÓN AZUL ======================================================== -->

<button
    type="button"
    class="azul"
    onclick="comprobarErrores()"
>
    Comprobar errores
</button>

<!-- ======================================================== BOTÓN VERDE ======================================================== -->

<button
    type="submit"
    class="verde"
>
    Comprobar Sudoku
</button>

<!-- ======================================================== NUEVO SUDOKU ======================================================== -->

<button
    type="submit"
    name="nuevo"
    value="1"
    class="gris"
>
    Nuevo Sudoku
</button>

<!-- BOTÓN LIMPIAR -->
<button
    type="button"
    class="gris"
    onclick="limpiarTablero()"
>
    Limpiar tablero
</button>


    <?php  /*<a
    href="generarPDF2.php"
    class="boton-pdf"
    target="_blank"
>
    🖨️ Imprimir 3 sudokus
</a> */?>

    <a
    href="generarPDF2.php"
    class="boton-pdf"
    target="_blank"
>
    🖨️ Imprimir todos los sudokus
</a>





</div>

</form>

<script> 
// ============================================================ 
// // SOLO PERMITIR NÚMEROS 1-9 
// // ============================================================ 
// 
document .querySelectorAll('.editable') .forEach(function(celda) { celda.addEventListener( 'keydown', function(evento) { const permitidas = [ 'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End' ]; if ( permitidas.includes( evento.key ) ) { return; } if ( !/^[1-9]$/.test( evento.key ) ) { evento.preventDefault(); } } ); celda.addEventListener( 'input', function() { this.value = this.value.replace( /[^1-9]/g, '' ); } ); }); 
// // ============================================================ 
// // OBTENER CELDA 
// // ============================================================ 
// 
// ============================================================
// OBTENER CUALQUIER CELDA DEL TABLERO
// Incluye tanto las fijas como las editables
// ============================================================

function obtenerCelda(fila, columna) {

    return document.querySelector(
        `.celda[data-fila="${fila}"][data-columna="${columna}"]`
    );
}

// ============================================================
// COMPROBAR UNA CELDA
// ============================================================

function comprobarCelda(celda) {

    // Quitamos el error anterior
    celda.classList.remove('error');

    // Si está vacía, no hay nada que comprobar
    if (celda.value === '') {
        return;
    }

    const fila = parseInt(celda.dataset.fila);
    const columna = parseInt(celda.dataset.columna);

    const numero = celda.value;


    // ========================================================
    // FILA
    // ========================================================

    for (let c = 0; c < 9; c++) {

        if (c === columna) {
            continue;
        }

        const otra = obtenerCelda(fila, c);

        if (otra && otra.value === numero) {

            celda.classList.add('error');

            return;
        }
    }


    // ========================================================
    // COLUMNA
    // ========================================================

    for (let f = 0; f < 9; f++) {

        if (f === fila) {
            continue;
        }

        const otra = obtenerCelda(f, columna);

        if (otra && otra.value === numero) {

            celda.classList.add('error');

            return;
        }
    }


    // ========================================================
    // BLOQUE 3x3
    // ========================================================

    const inicioFila =
        Math.floor(fila / 3) * 3;

    const inicioColumna =
        Math.floor(columna / 3) * 3;


    for (
        let f = inicioFila;
        f < inicioFila + 3;
        f++
    ) {

        for (
            let c = inicioColumna;
            c < inicioColumna + 3;
            c++
        ) {

            if (
                f === fila &&
                c === columna
            ) {
                continue;
            }

            const otra =
                obtenerCelda(f, c);


            if (
                otra &&
                otra.value === numero
            ) {

                celda.classList.add('error');

                return;
            }
        }
    }
}


// ============================================================
// COMPROBAR TODAS LAS CASILLAS
// ============================================================
function comprobarErrores() {

    const celdas =
        document.querySelectorAll('.celda');

    // Primero eliminamos todos los errores
    celdas.forEach(function(celda) {

        celda.classList.remove('error');

    });


    // Después comprobamos todas
    celdas.forEach(function(celda) {

        comprobarCelda(celda);

    });
} 






function limpiarTablero() {

    const celdas =
        document.querySelectorAll('.editable');

    celdas.forEach(function(celda) {

        // Vaciar solamente las casillas editables
        celda.value = '';

        // Quitar posibles errores
        celda.classList.remove('error');

    });
}




</script>

</body>

</html>