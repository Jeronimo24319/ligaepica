<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

$grupo_id = $_SESSION['grupo_activo'];
$usuario_id = $_SESSION['id'];

$stmt = $conn->prepare("
UPDATE invitaciones
SET activa = 0
WHERE id_grupo = ?
");

$stmt->bind_param("i",$grupo_id);
$stmt->execute();

$token = bin2hex(random_bytes(16));

$stmt = $conn->prepare("
INSERT INTO invitaciones
(id_grupo, token, creada_por, activa, usos, limite_usos)
VALUES (?, ?, ?, 1, 0, 1)
");

$stmt->bind_param("isi",$grupo_id,$token,$usuario_id);
$stmt->execute();

header("Location: miembros.php?grupo=".$grupo_id);
exit;