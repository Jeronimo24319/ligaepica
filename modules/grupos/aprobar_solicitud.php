<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if(!isset($_GET['id'])){
header("Location: solicitudes.php");
exit;
}

$id = intval($_GET['id']);
$admin_id = $_SESSION['id'];

/* ===============================
   OBTENER SOLICITUD
================================ */

$stmt = $conn->prepare("
SELECT usuario_id, grupo_id
FROM solicitudes_grupo
WHERE id = ?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$sol = $stmt->get_result()->fetch_assoc();

if(!$sol){
header("Location: solicitudes.php");
exit;
}

$usuario_id = $sol['usuario_id'];
$grupo_id = $sol['grupo_id'];


/* ===============================
   COMPROBAR SI YA ESTÁ EN EL GRUPO
================================ */

$stmtCheck = $conn->prepare("
SELECT id
FROM ligaepica_grupos
WHERE usuario_id = ?
AND grupo_id = ?
LIMIT 1
");

$stmtCheck->bind_param("ii",$usuario_id,$grupo_id);
$stmtCheck->execute();

$existe = $stmtCheck->get_result();


/* ===============================
   INSERTAR EN EL GRUPO
================================ */

if($existe->num_rows == 0){

$stmtInsert = $conn->prepare("
INSERT INTO ligaepica_grupos (grupo_id,usuario_id,rol_grupo)
VALUES (?,?,'miembro')
");

$stmtInsert->bind_param("ii",$grupo_id,$usuario_id);
$stmtInsert->execute();

}


/* ===============================
   ACTUALIZAR SOLICITUD
================================ */

$stmtUpdate = $conn->prepare("
UPDATE solicitudes_grupo
SET estado='aprobado',
revisado_por=?,
fecha_revision=NOW()
WHERE id=?
");

$stmtUpdate->bind_param("ii",$admin_id,$id);
$stmtUpdate->execute();


header("Location: solicitudes.php");
exit;