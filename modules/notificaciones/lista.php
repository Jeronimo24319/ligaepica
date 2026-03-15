<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$usuario = $_SESSION['id'] ?? 0;

if(!$usuario){
echo json_encode([]);
exit;
}

$stmt = $conn->prepare("
SELECT n.*, u.nombre
FROM notificaciones n
LEFT JOIN usuarios u ON u.id = n.usuario_origen
WHERE n.usuario_id = ?
AND n.leido = 0
ORDER BY n.fecha DESC
LIMIT 20
");

$stmt->bind_param("i",$usuario);
$stmt->execute();

$res = $stmt->get_result();

$notificaciones = [];

while($row = $res->fetch_assoc()){

$mensaje = strtolower($row['mensaje']);

$url = "/ligaepica_v2/modules/dashboard/index.php";

if(str_contains($mensaje,"actividad")){
$url = "/ligaepica_v2/modules/actividades/index.php";
}

elseif(str_contains($mensaje,"participación")){
$url = "/ligaepica_v2/modules/actividades/mis_participaciones.php";
}

elseif(str_contains($mensaje,"grupo")){
$url = "/ligaepica_v2/modules/grupos/index.php";
}

$row['url'] = "/ligaepica_v2/modules/notificaciones/leer.php?id=".$row['id']."&redir=".$url;

if(empty($row['nombre'])){
$row['nombre'] = "Sistema";
}

$notificaciones[] = $row;

}

echo json_encode($notificaciones);