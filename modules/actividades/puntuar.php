<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['actividad_id'])) {
header("Location: index.php");
exit;
}

$actividad_id = intval($_POST['actividad_id']);

/* ================================
   OBTENER ACTIVIDAD
================================ */

$stmt = $conn->prepare("
SELECT id, id_grupo, puntos, fecha, hora_fin
FROM actividades
WHERE id = ?
");

$stmt->bind_param("i", $actividad_id);
$stmt->execute();
$res = $stmt->get_result();
$actividad = $res->fetch_assoc();

if (!$actividad) {
die("Actividad no encontrada.");
}

/* ================================
   CONTROL DE FECHA (3 HORAS DESPUÉS)
================================ */

$horaFin = $actividad['hora_fin'] ?? '00:00:00';

$fechaHoraFin = $actividad['fecha'] . ' ' . $horaFin;

$fechaLimite = date('Y-m-d H:i:s', strtotime($fechaHoraFin . ' +3 hours'));

$ahora = date('Y-m-d H:i:s');

if ($ahora > $fechaLimite) {
die("El tiempo para puntuar esta actividad ha terminado.");
}

/* ================================
   VERIFICAR QUE PERTENECE AL GRUPO
================================ */

$stmtGrupo = $conn->prepare("
SELECT 1
FROM ligaepica_grupos
WHERE usuario_id = ?
AND grupo_id = ?
LIMIT 1
");

$stmtGrupo->bind_param("ii", $usuario_id, $actividad['id_grupo']);
$stmtGrupo->execute();
$pertenece = $stmtGrupo->get_result();

if ($pertenece->num_rows === 0) {
die("No perteneces a este grupo.");
}

/* ================================
   BUSCAR PARTICIPACIÓN
================================ */

$stmtCheck = $conn->prepare("
SELECT participo, estado
FROM actividad_participantes
WHERE id_usuario = ?
AND id_actividad = ?
LIMIT 1
");

$stmtCheck->bind_param("ii", $usuario_id, $actividad_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows === 0) {
die("Registro de participación no encontrado.");
}

$registro = $resCheck->fetch_assoc();

/* DEBE HABER CONFIRMADO ASISTENCIA */

if ($registro['participo'] != 1) {
die("Debes confirmar asistencia antes de puntuar.");
}

/* YA PUNTUADO */

if ($registro['estado'] === 'aprobado') {
die("Ya has puntuado esta actividad.");
}

/* ================================
   ACTUALIZAR PARTICIPACIÓN
================================ */

$stmtUpdate = $conn->prepare("
UPDATE actividad_participantes
SET
puntos_ganados = ?,
estado = 'aprobado'
WHERE id_usuario = ?
AND id_actividad = ?
");

$stmtUpdate->bind_param(
"iii",
$actividad['puntos'],
$usuario_id,
$actividad_id
);

$stmtUpdate->execute();

header("Location: index.php");
exit;
?>