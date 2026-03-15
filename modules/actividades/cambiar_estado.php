<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if ($_SESSION['rol'] !== 'admin') {
    die("Acceso restringido.");
}

$id = intval($_GET['id']);
$estado = $_GET['estado'];

if (!in_array($estado, ['aprobado', 'rechazado'])) {
    die("Estado inválido.");
}

$stmt = $conn->prepare("
    UPDATE actividad_participantes
    SET estado = ?
    WHERE id = ?
");
$stmt->bind_param("si", $estado, $id);
$stmt->execute();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;