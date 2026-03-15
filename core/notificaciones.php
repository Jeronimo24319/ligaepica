<?php

function crearNotificacion($conn,$usuario,$origen,$grupo,$actividad,$tipo,$mensaje){

$stmt=$conn->prepare("
INSERT INTO notificaciones
(usuario_id,usuario_origen,grupo_id,actividad_id,tipo,mensaje)
VALUES (?,?,?,?,?,?)
");

$stmt->bind_param(
"iiiiss",
$usuario,
$origen,
$grupo,
$actividad,
$tipo,
$mensaje
);

$stmt->execute();

}