<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if (!isset($_GET['usuario'])) {
    header("Location: privado.php");
    exit;
}

$usuario_actual  = $_SESSION['id'];
$usuario_destino = intval($_GET['usuario']);
$grupo_activo    = $_SESSION['grupo_activo'];

/* No permitir conversación contigo mismo */
if ($usuario_actual == $usuario_destino) {
    header("Location: privado.php");
    exit;
}

/* Verificar que el usuario destino pertenece al grupo activo */
$stmtCheck = $conn->prepare("
    SELECT 1 FROM usuario_grupo
    WHERE usuario_id = ?
    AND grupo_id = ?
");
$stmtCheck->bind_param("ii", $usuario_destino, $grupo_activo);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows === 0) {
    die("No puedes iniciar conversación con este usuario.");
}

/* Verificar si ya existe conversación */
$stmtExiste = $conn->prepare("
    SELECT id FROM conversaciones_privadas
    WHERE grupo_id = ?
    AND (
        (usuario1 = ? AND usuario2 = ?)
        OR
        (usuario1 = ? AND usuario2 = ?)
    )
    LIMIT 1
");

$stmtExiste->bind_param(
    "iiiii",
    $grupo_activo,
    $usuario_actual,
    $usuario_destino,
    $usuario_destino,
    $usuario_actual
);

$stmtExiste->execute();
$resExiste = $stmtExiste->get_result();

if ($resExiste->num_rows > 0) {
    $conv = $resExiste->fetch_assoc();
    header("Location: conversacion.php?id=" . $conv['id']);
    exit;
}

/* Crear nueva conversación */
$stmtCrear = $conn->prepare("
    INSERT INTO conversaciones_privadas
    (grupo_id, usuario1, usuario2)
    VALUES (?, ?, ?)
");

$stmtCrear->bind_param(
    "iii",
    $grupo_activo,
    $usuario_actual,
    $usuario_destino
);

$stmtCrear->execute();

$nueva_id = $stmtCrear->insert_id;

header("Location: conversacion.php?id=" . $nueva_id);
exit;