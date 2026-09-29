<?php // ============================================================ 
// CONFIGURACIÓN // ============================================================ 
$archivoCSV = __DIR__ . '/data/sudokus.csv'; 
// ============================================================ 
// FUNCIONES // ============================================================ /** * Convierte la cadena del CSV en una matriz 9x9. * * En el CSV: * * * = casilla vacía * 1-9 = número fijo */ 
function convertirTablero(string $cadena): array { $tablero = []; for ($fila = 0; $fila < 9; $fila++) { $tablero[$fila] = []; for ($columna = 0; $columna < 9; $columna++) { $posicion = ($fila * 9) + $columna; $caracter = $cadena[$posicion]; if ($caracter === '*') { $tablero[$fila][$columna] = 0; } else { $tablero[$fila][$columna] = (int) $caracter; } } } return $tablero; } /** * Indica qué casillas son originales/fijas. */ 
function obtenerFijas(string $cadena): array { $fijas = []; for ($fila = 0; $fila < 9; $fila++) { $fijas[$fila] = []; for ($columna = 0; $columna < 9; $columna++) { $posicion = ($fila * 9) + $columna; $fijas[$fila][$columna] = ($cadena[$posicion] !== '*'); } } return $fijas; } /** * Comprueba filas, columnas y bloques 3x3. * * Los ceros representan casillas vacías * y no se tienen en cuenta. */ function tableroValido(array $tablero): bool { // ======================================================== // FILAS // ======================================================== 
for ($fila = 0; $fila < 9; $fila++) { $vistos = []; for ($columna = 0; $columna < 9; $columna++) { $numero = $tablero[$fila][$columna]; if ($numero === 0) { continue; } if (isset($vistos[$numero])) { return false; } $vistos[$numero] = true; } } 
// ======================================================== 
// COLUMNAS // ======================================================== 
for ($columna = 0; $columna < 9; $columna++) { $vistos = []; for ($fila = 0; $fila < 9; $fila++) { $numero = $tablero[$fila][$columna]; if ($numero === 0) { continue; } if (isset($vistos[$numero])) { return false; } $vistos[$numero] = true; } } 
// ======================================================== // BLOQUES 3x3 
// ======================================================== 
for ($inicioFila = 0; $inicioFila < 9; $inicioFila += 3) { for ( $inicioColumna = 0; $inicioColumna < 9; $inicioColumna += 3 ) { $vistos = []; for ($fila = $inicioFila; $fila < $inicioFila + 3; $fila++) { for ( $columna = $inicioColumna; $columna < $inicioColumna + 3; $columna++ ) { $numero = $tablero[$fila][$columna]; if ($numero === 0) { continue; } if (isset($vistos[$numero])) { return false; } $vistos[$numero] = true; } } } } return true; } 
/** * Comprueba si no quedan casillas vacías. */ function tableroCompleto(array $tablero): bool { for ($fila = 0; $fila < 9; $fila++) { for ($columna = 0; $columna < 9; $columna++) { if ($tablero[$fila][$columna] === 0) { return false; } } } return true; } /** * Un Sudoku está resuelto si: * * 1. Está completo. * 2. No contiene conflictos. * * No se compara contra una solución concreta. */ 
function sudokuResuelto(array $tablero): bool { return ( tableroCompleto($tablero) && tableroValido($tablero) ); } 
// ============================================================ // LEER CSV // ============================================================ 
$sudokus = []; if (!file_exists($archivoCSV)) { die('No existe el archivo sudokus.csv'); } $handle = fopen($archivoCSV, 'r'); if ($handle === false) { die('No se puede abrir el archivo sudokus.csv'); } // Saltamos la cabecera 
fgetcsv($handle); while (($fila = fgetcsv($handle)) !== false) { // Necesitamos al menos: 
    // id, dificultad, tablero 
    if (count($fila) < 3) { continue; } // Quitamos espacios, saltos y posibles comillas simples 
$id = trim( $fila[0], " \t\n\r\0\x0B'" ); $dificultad = trim( $fila[1], " \t\n\r\0\x0B'" ); $cadena = trim( $fila[2], " \t\n\r\0\x0B'" ); 
// ======================================================== // VALIDAR LONGITUD // ======================================================== 
if (strlen($cadena) !== 81) { continue; } // ======================================================== 
// VALIDAR CARACTERES // // Solo: // * 1 2 3 4 5 6 7 8 9 // ======================================================== 
    if (!preg_match('/^[*1-9]{81}$/', $cadena)) { continue; } // ======================================================== 
    // CONVERTIR A MATRIZ // ======================================================== 
    $tableroInicial = convertirTablero($cadena); // ======================================================== 
    // EL TABLERO INICIAL NO PUEDE TENER CONFLICTOS // ======================================================== 
    if (!tableroValido($tableroInicial)) { continue; } // ======================================================== 
    // GUARDAR // ======================================================== 
    $sudokus[] = [ 'id' => $id, 'dificultad' => $dificultad, 'cadena' => $cadena, 'tablero' => $tableroInicial ]; } fclose($handle); // ============================================================ // COMPROBAR QUE HAY SUDOKUS // ============================================================ 
    if (empty($sudokus)) { die( 'No se ha encontrado ningún Sudoku válido en el CSV.' ); } 
    // ============================================================ // ELEGIR UN SUDOKU ALEATORIO // ============================================================ 
    $sudoku = $sudokus[array_rand($sudokus)]; // Tablero original 
    $tableroInicial = $sudoku['tablero']; // Tablero que mostraremos 
    $tablero = $tableroInicial; // Casillas que no puede modificar el usuario 
    $fijas = obtenerFijas($sudoku['cadena']); 
// ============================================================ 
// RESULTADO // ============================================================ 
$resultado = null; // ============================================================ 
// PROCESAR FORMULARIO // ============================================================ 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $jugador = []; 
// ======================================================== // RECONSTRUIR TABLERO DEL JUGADOR // ======================================================== 
for ($fila = 0; $fila < 9; $fila++) { $jugador[$fila] = []; for ($columna = 0; $columna < 9; $columna++) { $nombre = 'c_' . $fila . '_' . $columna; $valor = $_POST[$nombre] ?? ''; // ================================================= 
// CASILLA FIJA // ================================================= 
if ($fijas[$fila][$columna]) { // Nunca aceptamos el valor enviado por el 
// navegador para una casilla fija. // // Utilizamos el valor original del CSV. 
$jugador[$fila][$columna] = $tableroInicial[$fila][$columna]; continue; } 
// ================================================= // CASILLA DEL JUGADOR // ================================================= 
if (is_string($valor) && preg_match('/^[1-9]$/', $valor)) { $jugador[$fila][$columna] = (int) $valor; } else { $jugador[$fila][$columna] = 0; } } } // ======================================================== // MOSTRAR DE NUEVO LO QUE HA ESCRITO // ======================================================== 
$tablero = $jugador; // ======================================================== // COMPROBAR // ======================================================== 
if (!tableroValido($jugador)) { $resultado = 'incorrecto'; } elseif (!tableroCompleto($jugador)) { $resultado = 'incompleto'; } else { /* * IMPORTANTE: * * No comparamos contra una solución almacenada. * * Si el Sudoku admite varias soluciones, * cualquier solución válida será aceptada. */ $resultado = 'correcto'; } } ?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"




<title>Sudoku</title>

<style> /* ============================================================ 
GENERAL ============================================================ */ 
* { box-sizing: border-box; }
 body { margin: 0; padding: 30px 15px; background: #f3f4f6; color: #111827; font-family: Arial, Helvetica, sans-serif; text-align: center; } /* ============================================================ CABECERA ============================================================ */ h1 { margin: 0 0 6px; font-size: 32px; } .info { margin-bottom: 22px; color: #6b7280; font-size: 15px; } /* ============================================================ MENSAJES ============================================================ */ .mensaje { max-width: 500px; margin: 0 auto 20px; padding: 14px 18px; border-radius: 8px; font-weight: bold; } .mensaje.correcto { background: #dcfce7; color: #166534; border: 1px solid #86efac; } .mensaje.incorrecto { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; } .mensaje.incompleto { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; } /* ============================================================ TABLERO ============================================================ */ .sudoku { margin: 0 auto 25px; border-collapse: collapse; background: white; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); } .sudoku td { width: 55px; height: 55px; padding: 0; border: 1px solid #9ca3af; } /* ============================================================ BORDES DE LOS BLOQUES 3x3 ============================================================ */ .sudoku tr:nth-child(3n) td { border-bottom: 3px solid #111827; } .sudoku td:nth-child(3n) { border-right: 3px solid #111827; } .sudoku tr:first-child td { border-top: 3px solid #111827; } .sudoku td:first-child { border-left: 3px solid #111827; } /* ============================================================ TODAS LAS CELDAS ============================================================ */ .sudoku input { width: 100%; height: 100%; padding: 0; border: 0; outline: none; text-align: center; font-size: 25px; font-family: Arial, sans-serif; } /* ============================================================ NÚMEROS ORIGINALES ============================================================ */ .sudoku .fijo { background: #e5e7eb; color: #111827; font-weight: bold; cursor: default; } /* ============================================================ CASILLAS DEL JUGADOR ============================================================ */ .sudoku .editable { background: white; color: #2563eb; font-weight: 500; } .sudoku .editable:focus { background: #dbeafe; } /* ============================================================ ERROR DE FILA/COLUMNA/BLOQUE ============================================================ */ .sudoku .editable.error { background: #fee2e2; color: #dc2626; font-weight: bold; } .sudoku .editable.error:focus { background: #fecaca; } /* ============================================================ CASILLA VÁLIDA ============================================================ */ .sudoku .editable.valida { color: #2563eb; } /* ============================================================ BOTONES ============================================================ */ 
.botones {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

button {
    padding: 12px 22px;
    border: 0;
    border-radius: 7px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    font-weight: bold;
}


/* BOTÓN AZUL */

.comprobar-errores {
    background: #2563eb;
}

.comprobar-errores:hover {
    background: #1d4ed8;
}


/* BOTÓN VERDE */

.comprobar-sudoku {
    background: #16a34a;
}

.comprobar-sudoku:hover {
    background: #15803d;
}


/* NUEVO SUDOKU */

.nuevo {
    background: #6b7280;
}

.nuevo:hover {
    background: #4b5563;
}
 /* ============================================================ MÓVIL ============================================================ */ 
 @media (max-width: 600px) { body { padding: 20px 8px; } h1 { font-size: 27px; } .sudoku td { width: 10.5vw; height: 10.5vw; } .sudoku input { font-size: 5vw; } } </style>

</head>

<body>

<h1>Sudoku</h1>

<div class="info">

<?php echo htmlspecialchars($sudoku['id']) ?>

&nbsp;·&nbsp;

Dificultad:

<?php echo htmlspecialchars($sudoku['dificultad']) ?>

</div>

<?php 
// ============================================================ 
// MENSAJE DEL RESULTADO 
// ============================================================ 
if ($resultado === 'correcto'): ?>

<div class="mensaje correcto">

    🎉 ¡Sudoku correcto!

    <br>

    Has completado correctamente el tablero.

</div>

<?php elseif ($resultado === 'incompleto'): ?>

<div class="mensaje incompleto">

    ⚠️ El Sudoku está incompleto.

    <br>

    Rellena todas las casillas.

</div>

<?php elseif ($resultado === 'incorrecto'): ?>

<div class="mensaje incorrecto">

    ❌ Hay números repetidos.

    <br>

    Revisa las filas, columnas y bloques 3×3.

</div>

<?php endif; ?>

<form method="POST" id="sudokuForm" >

<table class="sudoku">

<?php for ($fila = 0; $fila < 9; $fila++): ?>

<tr>

<?php for ($columna = 0; $columna < 9; $columna++): ?>

<td>

<?php 
// ========================================================== 
// CASILLA FIJA 
// ========================================================== 
if ($fijas[$fila][$columna]): ?>

<input
    type="text"
    class="fijo celda"
    data-fila="<?php echo $fila ?>"
    data-columna="<?php echo $columna ?>"
    value="<?php echo htmlspecialchars(
    (string) $tablero[$fila][$columna]
) ?>"
    readonly
>

<?php // ========================================================== 
// CASILLA DEL JUGADOR 
// ========================================================== 
else: ?>

<input
    type="text"
    class="editable celda"
    name="c_<?php echo $fila ?>_<?php echo $columna ?>"
    data-fila="<?php echo $fila ?>"
    data-columna="<?php echo $columna ?>"
    maxlength="1"
    inputmode="numeric"
    autocomplete="off"
    value="<?php echo $tablero[$fila][$columna] !== 0
    ? htmlspecialchars(
    (string) $tablero[$fila][$columna]
)
    : '' ?>"
>

<?php endif; ?>

</td>

<?php endfor; ?>

</tr>

<?php endfor; ?>

</table>

<div class="botones">

    <!-- BOTÓN AZUL -->
    <button
        type="button"
        class="comprobar-errores"
        onclick="comprobarErrores()"
    >
        Comprobar errores
    </button>


    <!-- BOTÓN VERDE -->
    <?php if (tableroCompleto($tablero)): ?>

        <button
            type="submit"
            class="comprobar-sudoku"
        >
            Comprobar Sudoku
        </button>

    <?php endif; ?>


    <!-- NUEVO SUDOKU -->
    <button
        type="button"
        class="nuevo"
        onclick="nuevoSudoku()"
    >
        Nuevo Sudoku
    </button>



    

</div>

</form>

<script> /* ============================================================ REFERENCIAS ============================================================ */ const todasLasCeldas = document.querySelectorAll('.celda'); const celdasEditables = document.querySelectorAll('.editable'); /* ============================================================ OBTENER UNA CELDA ============================================================ */
function obtenerCelda(fila, columna) { return document.querySelector( `.celda[data-fila="${fila}"][data-columna="${columna}"]` ); } /* ============================================================ COMPROBAR CONFLICTO DE UNA CELDA ============================================================ */
function tieneConflicto(fila, columna) { const celda = obtenerCelda(fila, columna); if (!celda) { return false; } const numero = celda.value; // Casilla vacía
if (numero === '') { return false; } /* ======================================================== FILA ======================================================== */
for (let c = 0; c < 9; c++) { if (c === columna) { continue; } const otra = obtenerCelda(fila, c); if ( otra && otra.value === numero ) { return true; } } /* ======================================================== COLUMNA ======================================================== */
for (let f = 0; f < 9; f++) { if (f === fila) { continue; } const otra = obtenerCelda(f, columna); if ( otra && otra.value === numero ) { return true; } } /* ======================================================== BLOQUE 3x3 ======================================================== */
const inicioFila = Math.floor(fila / 3) * 3; const inicioColumna = Math.floor(columna / 3) * 3; for ( let f = inicioFila; f < inicioFila + 3; f++ ) { for ( let c = inicioColumna; c < inicioColumna + 3; c++ ) { if ( f === fila && c === columna ) { continue; } const otra = obtenerCelda(f, c); if ( otra && otra.value === numero ) { return true; } } } return false; } /* ============================================================ COMPROBAR TODO EL TABLERO EN EL NAVEGADOR ============================================================ */ 
function comprobarTablero() { // Primero quitamos estados anteriores 
celdasEditables.forEach(function(celda) { celda.classList.remove('error'); celda.classList.remove('valida'); }); 
// Comprobamos cada casilla editable 
celdasEditables.forEach(function(celda) { if (celda.value === '') { return; } const fila = parseInt( celda.dataset.fila, 10 ); const columna = parseInt( celda.dataset.columna, 10 ); if ( tieneConflicto( fila, columna ) ) { celda.classList.add('error'); } else { celda.classList.add('valida'); } }); } /* ============================================================ SOLO PERMITIR 1-9 ============================================================ */
celdasEditables.forEach(function(celda) { /* ======================================================== TECLADO ======================================================== */ 
celda.addEventListener( 'keydown', function(evento) { const teclasPermitidas = [ 'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End' ]; // Teclas de navegación/control 
if ( teclasPermitidas.includes( evento.key ) ) { return; } // Solo 1-9 
if ( !/^[1-9]$/.test( evento.key ) ) { evento.preventDefault(); } } ); /* ======================================================== INPUT ======================================================== */ 
celda.addEventListener( 'input', function() { // Eliminar cualquier cosa que no sea 1-9 
this.value = this.value.replace( /[^1-9]/g, '' ); comprobarTablero(); } ); 
/* ======================================================== PEGAR ======================================================== */ 
celda.addEventListener( 'paste', function(evento) { evento.preventDefault(); const texto = evento.clipboardData .getData('text') .trim(); if ( /^[1-9]$/.test(texto) ) { this.value = texto; } else { this.value = ''; } comprobarTablero(); } ); }); /* ============================================================ NUEVO SUDOKU ============================================================ */ 
function nuevoSudoku() { window.location.href = window.location.pathname; } /* ============================================================ COMPROBACIÓN INICIAL ============================================================ */ comprobarTablero(); </script>

</body>

</html>