<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];
$grupo_id = intval($_GET['grupo'] ?? 0);

if($grupo_id <= 0){
    die("Grupo no válido.");
}

/* ===============================
   COMPROBAR QUE EL GRUPO EXISTE
================================ */

$stmt = $conn->prepare("
SELECT id, activo
FROM grupos
WHERE id = ?
LIMIT 1
");

$stmt->bind_param("i",$grupo_id);
$stmt->execute();

$grupo = $stmt->get_result()->fetch_assoc();

if(!$grupo){
die("El grupo no existe.");
}

if($grupo['activo'] == 0){
die("Este grupo está inactivo.");
}


/* ===============================
   COMPROBAR SI YA ES MIEMBRO
================================ */

$stmt = $conn->prepare("
SELECT id 
FROM ligaepica_grupos
WHERE usuario_id = ?
AND grupo_id = ?
");

$stmt->bind_param("ii",$usuario_id,$grupo_id);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
die("Ya perteneces a este grupo.");
}


/* ===============================
   COMPROBAR SI YA EXISTE SOLICITUD
================================ */

$stmt = $conn->prepare("
SELECT id 
FROM solicitudes_grupo
WHERE usuario_id = ?
AND grupo_id = ?
AND estado = 'pendiente'
");

$stmt->bind_param("ii",$usuario_id,$grupo_id);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
die("Ya tienes una solicitud pendiente.");
}


/* ===============================
   CREAR SOLICITUD
================================ */

$stmt = $conn->prepare("
INSERT INTO solicitudes_grupo
(usuario_id, grupo_id, estado, fecha_solicitud)
VALUES (?, ?, 'pendiente', NOW())
");

$stmt->bind_param("ii",$usuario_id,$grupo_id);
$stmt->execute();


header("Location: index.php?solicitud=enviada");
exit;