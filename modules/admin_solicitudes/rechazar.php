<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if ($_SESSION['rol'] !== 'admin') {
    die("Acceso restringido.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    UPDATE actividad_participantes
    SET estado = 'rechazado', puntos_ganados = 0
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: index.php");
exit;