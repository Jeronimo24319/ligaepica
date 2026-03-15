<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if(!isset($_GET['id'])){
header("Location: index.php");
exit;
}

$id = intval($_GET['id']);

/* ===============================
   OBTENER ESTADO DEL GRUPO
================================ */

$stmt = $conn->prepare("
SELECT activo
FROM grupos
WHERE id = ?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();
$grupo = $result->fetch_assoc();

if(!$grupo){
header("Location: index.php");
exit;
}


/* ===============================
   CAMBIAR ESTADO
================================ */

$nuevo_estado = ($grupo['activo'] == 1) ? 0 : 1;

$update = $conn->prepare("
UPDATE grupos
SET activo = ?
WHERE id = ?
");

$update->bind_param("ii",$nuevo_estado,$id);
$update->execute();


header("Location: index.php");
exit;