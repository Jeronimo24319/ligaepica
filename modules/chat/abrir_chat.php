<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_actual = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'];

$otro = intval($_GET['usuario'] ?? 0);

if(!$otro){
header("Location: privado.php");
exit;
}

/* BUSCAR CONVERSACIÓN EXISTENTE */

$stmt=$conn->prepare("
SELECT id
FROM conversaciones_privadas
WHERE grupo_id=?
AND (
(usuario1=? AND usuario2=?)
OR
(usuario1=? AND usuario2=?)
)
");

$stmt->bind_param("iiiii",
$grupo_activo,
$usuario_actual,$otro,
$otro,$usuario_actual
);

$stmt->execute();
$res=$stmt->get_result()->fetch_assoc();

if($res){

$id=$res['id'];

}else{

$stmt=$conn->prepare("
INSERT INTO conversaciones_privadas
(grupo_id,usuario1,usuario2)
VALUES (?,?,?)
");

$stmt->bind_param("iii",$grupo_activo,$usuario_actual,$otro);
$stmt->execute();

$id=$conn->insert_id;

}

header("Location: conversacion.php?id=".$id);
exit;