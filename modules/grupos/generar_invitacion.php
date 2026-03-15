<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

/* ===============================
OBTENER DATOS
=============================== */

$grupo_id = $_SESSION['grupo_activo'];
$usuario_id = $_SESSION['id'];


/* ===============================
GENERAR TOKEN SEGURO
=============================== */

$token = bin2hex(random_bytes(16));


/* ===============================
DESACTIVAR INVITACIONES ANTERIORES
=============================== */

$stmt = $conn->prepare("
UPDATE invitaciones
SET activa = 0
WHERE id_grupo = ?
");

$stmt->bind_param("i",$grupo_id);
$stmt->execute();


/* ===============================
CREAR NUEVA INVITACIÓN
=============================== */

$stmt = $conn->prepare("
INSERT INTO invitaciones
(id_grupo, token, creada_por, activa, fecha_creacion, usos, limite_usos)
VALUES (?, ?, ?, 1, NOW(), 0, 100)
");

$stmt->bind_param("isi",$grupo_id,$token,$usuario_id);
$stmt->execute();


/* ===============================
VOLVER A MIEMBROS
=============================== */

header("Location: miembros.php?grupo=".$grupo_id);
exit;
?>