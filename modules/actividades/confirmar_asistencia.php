<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'] ?? 0;

$actividad_id = intval($_GET['actividad'] ?? 0);
$estado = $_GET['estado'] ?? '';

if(!$usuario_id || !$actividad_id){
    header("Location: index.php");
    exit;
}

/* ===============================
   DETERMINAR PARTICIPACIÓN
================================ */

$participo = ($estado === 'si') ? 1 : 0;

/* ===============================
   INSERTAR O ACTUALIZAR
================================ */

$stmt = $conn->prepare("
INSERT INTO actividad_participantes
(id_usuario, id_actividad, participo, puntos_ganados, estado)
VALUES (?, ?, ?, 0, 'pendiente')
ON DUPLICATE KEY UPDATE
participo = VALUES(participo)
");

$stmt->bind_param("iii", $usuario_id, $actividad_id, $participo);
$stmt->execute();

/* ===============================
   REDIRIGIR
================================ */

header("Location: index.php");
exit;