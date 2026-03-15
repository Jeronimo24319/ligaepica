<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mis_participaciones.php");
    exit;
}

$participacion_id = intval($_POST['participacion_id']);
$mensaje = trim($_POST['mensaje']);

if ($mensaje !== '') {

    $stmt = $conn->prepare("
        INSERT INTO actividad_mensajes
        (id_participacion, id_usuario, mensaje)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iis", $participacion_id, $usuario_id, $mensaje);
    $stmt->execute();
}

header("Location: mis_participaciones.php");
exit;