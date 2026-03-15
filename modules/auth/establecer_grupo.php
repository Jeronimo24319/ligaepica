<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../config/database.php";

/* comprobar login */

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

/* comprobar grupo recibido */

if (!isset($_POST["grupo_id"])) {
    header("Location: seleccionar_grupo.php");
    exit();
}

$grupo_id = intval($_POST["grupo_id"]);
$usuario_id = intval($_SESSION["id"]);

/* comprobar que el usuario pertenece al grupo */

$stmtCheck = $conn->prepare("
SELECT rol_grupo
FROM ligaepica_grupos
WHERE usuario_id = ? AND grupo_id = ?
LIMIT 1
");

$stmtCheck->bind_param("ii", $usuario_id, $grupo_id);
$stmtCheck->execute();

$result = $stmtCheck->get_result();

if (!$result || $result->num_rows === 0) {

    /* el usuario no pertenece al grupo */
    header("Location: seleccionar_grupo.php");
    exit();
}

/* obtener rol del usuario */

$row = $result->fetch_assoc();

$_SESSION["grupo_activo"] = $grupo_id;
$_SESSION["rol_grupo"] = $row["rol_grupo"];

/* redirigir al dashboard */

header("Location: ../dashboard/index.php");
exit();