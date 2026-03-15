<?php

require_once __DIR__ . '/../config/database.php';

/* NO EXIGIR GRUPO ACTIVO EN ESTAS PÁGINAS */

$rutas_excluidas = [
'/modules/auth/seleccionar_grupo.php',
'/modules/auth/establecer_grupo.php',
'/modules/grupos/crear.php'
];

$uri_actual = $_SERVER['REQUEST_URI'];

foreach ($rutas_excluidas as $ruta) {
    if (strpos($uri_actual, $ruta) !== false) {
        return;
    }
}

/* SI YA HAY GRUPO ACTIVO NO HACER NADA */

if (isset($_SESSION['grupo_activo'])) {
    return;
}

/* COMPROBAR GRUPOS DEL USUARIO */

$usuario_id = $_SESSION['id'];

$stmt = $conn->prepare("
SELECT grupo_id
FROM ligaepica_grupos
WHERE usuario_id = ?
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$total_grupos = $result->num_rows;

/* SI NO TIENE GRUPOS → IR A CREAR GRUPO */

if ($total_grupos == 0) {

header("Location: /ligaepica_v2/modules/grupos/crear.php");
exit;

}

/* SI SOLO TIENE UN GRUPO → ACTIVAR AUTOMÁTICAMENTE */

if ($total_grupos == 1) {

$grupo = $result->fetch_assoc();

$_SESSION['grupo_activo'] = $grupo['grupo_id'];

return;

}

/* SI TIENE VARIOS → IR AL SELECTOR */

header("Location: /ligaepica_v2/modules/auth/seleccionar_grupo.php");
exit;