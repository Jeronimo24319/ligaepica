<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if(!isset($_GET['usuario'],$_GET['accion'],$_GET['grupo'])){
header("Location: miembros.php");
exit;
}

$usuario_id = intval($_GET['usuario']);
$accion = $_GET['accion'];
$grupo_id = intval($_GET['grupo']);
$admin_actual = $_SESSION['id'];


/* ==========================
EVITAR QUE SE CAMBIE A SÍ MISMO
========================== */

if($usuario_id == $admin_actual){
die("No puedes cambiar tu propio rol.");
}


/* ==========================
SI QUIEREN QUITAR ADMIN
COMPROBAR QUE NO SEA EL ÚLTIMO
========================== */

if($accion == "miembro"){

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM ligaepica_grupos
WHERE grupo_id=? AND rol_grupo='admin'
");

$stmt->bind_param("i",$grupo_id);
$stmt->execute();

$totalAdmins = $stmt->get_result()->fetch_row()[0];

if($totalAdmins <= 1){
die("Debe existir al menos un administrador en el grupo.");
}

$rol = "miembro";

}
else{

$rol = "admin";

}


/* ==========================
ACTUALIZAR ROL SOLO EN
EL GRUPO SELECCIONADO
========================== */

$stmt = $conn->prepare("
UPDATE ligaepica_grupos
SET rol_grupo=?
WHERE usuario_id=? AND grupo_id=?
");

$stmt->bind_param("sii",$rol,$usuario_id,$grupo_id);
$stmt->execute();


header("Location: miembros.php?grupo=".$grupo_id);
exit;