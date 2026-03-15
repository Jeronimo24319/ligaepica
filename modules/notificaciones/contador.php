<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$usuario = $_SESSION['id'] ?? 0;

if(!$usuario){
echo json_encode(["total"=>0]);
exit;
}

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM notificaciones
WHERE usuario_id = ?
AND leido = 0
");

$stmt->bind_param("i",$usuario);
$stmt->execute();

$total = $stmt->get_result()->fetch_row()[0] ?? 0;

echo json_encode([
"total"=>$total
]);