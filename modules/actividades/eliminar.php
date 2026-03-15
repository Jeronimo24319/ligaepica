<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$rol = $_SESSION['rol'] ?? '';
$grupo_activo = $_SESSION['grupo_activo'] ?? 0;
$hoy = date('Y-m-d');

if($rol!=='admin' && $rol!=='superadmin'){
die("No tienes permisos.");
}

if(!isset($_GET['id'])){
header("Location: index.php");
exit;
}

$id = intval($_GET['id']);

/* OBTENER DATOS DE ACTIVIDAD */

$stmt = $conn->prepare("
SELECT fecha
FROM actividades
WHERE id=? AND id_grupo=?
LIMIT 1
");

$stmt->bind_param("ii",$id,$grupo_activo);
$stmt->execute();

$res = $stmt->get_result();

if($res->num_rows==0){
die("Actividad no encontrada.");
}

$actividad = $res->fetch_assoc();

/* BLOQUEAR SI YA PASÓ */

if($actividad['fecha'] < $hoy){
die("No se pueden borrar actividades ya realizadas.");
}

/* COMPROBAR SI TIENE PUNTUACIONES */

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM actividad_participantes
WHERE id_actividad=? AND estado='aprobado'
");

$stmt->bind_param("i",$id);
$stmt->execute();

$tienePuntos = $stmt->get_result()->fetch_row()[0];

if($tienePuntos > 0){
die("No se puede borrar una actividad con puntuaciones.");
}

/* BORRAR PARTICIPANTES */

$stmt = $conn->prepare("
DELETE FROM actividad_participantes
WHERE id_actividad=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

/* BORRAR ACTIVIDAD */

$stmt = $conn->prepare("
DELETE FROM actividades
WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: index.php");
exit;