<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if (!isset($_GET['id'])) exit;

$conversacion_id = intval($_GET['id']);
$usuario_id = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'];

/* Validar conversación */
$stmtValidar = $conn->prepare("
    SELECT 1 FROM conversaciones_privadas
    WHERE id = ?
    AND grupo_id = ?
    AND (usuario1 = ? OR usuario2 = ?)
");

$stmtValidar->bind_param("iiii",
    $conversacion_id,
    $grupo_activo,
    $usuario_id,
    $usuario_id
);

$stmtValidar->execute();
$resValidar = $stmtValidar->get_result();

if ($resValidar->num_rows === 0) exit;

/* Obtener mensajes */
$stmt = $conn->prepare("
    SELECT id, remitente_id, mensaje, fecha
    FROM mensajes_privados
    WHERE conversacion_id = ?
    ORDER BY fecha ASC
");

$stmt->bind_param("i", $conversacion_id);
$stmt->execute();
$result = $stmt->get_result();

$mensajes = [];

while ($row = $result->fetch_assoc()) {
    $mensajes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($mensajes);