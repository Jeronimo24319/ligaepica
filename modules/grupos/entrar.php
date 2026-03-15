<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];
$grupo_id = intval($_GET['grupo'] ?? 0);

if(!$grupo_id){
die("Grupo no válido");
}

/* comprobar si pertenece al grupo */

$stmt = $conn->prepare("
SELECT rol_grupo
FROM ligaepica_grupos
WHERE usuario_id = ?
AND grupo_id = ?
");

$stmt->bind_param("ii",$usuario_id,$grupo_id);
$stmt->execute();

$res = $stmt->get_result()->fetch_assoc();

if($res){

$_SESSION['grupo_activo'] = $grupo_id;
$_SESSION['rol_grupo'] = $res['rol_grupo'];

header("Location: ../dashboard/index.php");
exit;

}

/* añadir usuario al grupo */


$stmt = $conn->prepare("
INSERT INTO ligaepica_grupos (grupo_id, usuario_id, rol_grupo)
VALUES (?, ?, 'miembro')
");

$stmt->bind_param("ii",$grupo_id,$usuario_id);
$stmt->execute();

/* INSCRIBIR AL USUARIO EN ACTIVIDADES FUTURAS */

$stmtAct = $conn->prepare("
INSERT IGNORE INTO actividad_participantes
(id_actividad, id_usuario, participo, puntos_ganados, estado)

SELECT
id,
?,
0,
0,
'pendiente'

FROM actividades
WHERE id_grupo = ?
AND fecha >= CURDATE()
");

$stmtAct->bind_param("ii",$usuario_id,$grupo_id);
$stmtAct->execute();

/* NOTIFICACIÓN */

$mensaje = "👤 " . $_SESSION['nombre'] . " se ha unido al grupo";

$stmtNotif = $conn->prepare("
INSERT INTO notificaciones (grupo_id, mensaje, url)
VALUES (?, ?, '/ligaepica_v2/modules/grupos/miembros.php')
");

$stmtNotif->bind_param("is", $grupo_id, $mensaje);
$stmtNotif->execute();

$_SESSION['grupo_activo'] = $grupo_id;
$_SESSION['rol_grupo'] = 'miembro';

header("Location: ../dashboard/index.php");
exit;