<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

if(!isset($_GET['id'])){
header("Location: /ligaepica_v2/modules/dashboard/index.php");
exit;
}

$id = intval($_GET['id']);
$usuario = $_SESSION['id'];

/* OBTENER NOTIFICACIÓN */

$stmt = $conn->prepare("
SELECT *
FROM notificaciones
WHERE id = ?
AND usuario_id = ?
LIMIT 1
");

$stmt->bind_param("ii",$id,$usuario);
$stmt->execute();

$notificacion = $stmt->get_result()->fetch_assoc();

if(!$notificacion){
header("Location: /ligaepica_v2/modules/dashboard/index.php");
exit;
}

/* MARCAR COMO LEÍDA */

$stmt = $conn->prepare("
UPDATE notificaciones
SET leido = 1
WHERE id = ?
");

$stmt->bind_param("i",$id);
$stmt->execute();

/* REDIRECCIÓN */

if(isset($_GET['redir']) && $_GET['redir'] != ""){
$url = $_GET['redir'];
}else{

$url = "/ligaepica_v2/modules/dashboard/index.php";

$mensaje = strtolower($notificacion['mensaje']);

if(str_contains($mensaje,"actividad")){
$url = "/ligaepica_v2/modules/actividades/index.php";
}

elseif(str_contains($mensaje,"participación")){
$url = "/ligaepica_v2/modules/actividades/mis_participaciones.php";
}

elseif(str_contains($mensaje,"grupo")){
$url = "/ligaepica_v2/modules/grupos/index.php";
}

}

header("Location: ".$url);
exit;