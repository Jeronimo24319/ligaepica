<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if(!isset($_GET['usuario'],$_GET['grupo'])){
header("Location: index.php");
exit;
}

$usuario_id = intval($_GET['usuario']);
$grupo_id = intval($_GET['grupo']);


/* ===============================
   COMPROBAR SI YA ESTÁ EN EL GRUPO
================================ */

$stmtCheck = $conn->prepare("
SELECT id
FROM ligaepica_grupos
WHERE usuario_id=? AND grupo_id=?
LIMIT 1
");

$stmtCheck->bind_param("ii",$usuario_id,$grupo_id);
$stmtCheck->execute();

$existe = $stmtCheck->get_result();


/* ===============================
   INSERTAR SOLO SI NO EXISTE
================================ */

if($existe->num_rows == 0){

$stmt = $conn->prepare("
INSERT INTO ligaepica_grupos (grupo_id,usuario_id,rol_grupo)
VALUES (?,?,'miembro')
");

$stmt->bind_param("ii",$grupo_id,$usuario_id);
$stmt->execute();

}

header("Location: miembros.php?grupo=".$grupo_id);
exit;