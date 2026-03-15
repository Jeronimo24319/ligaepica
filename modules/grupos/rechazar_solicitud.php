<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$id = intval($_GET['id']);
$admin_id = $_SESSION['id'];

$stmt = $conn->prepare("
UPDATE solicitudes_grupo
SET estado='rechazado',
revisado_por=?,
fecha_revision=NOW()
WHERE id=?
");

$stmt->bind_param("ii",$admin_id,$id);
$stmt->execute();

header("Location: solicitudes.php");
exit;