<?php

require_once '../../config/database.php';

$email = trim($_POST['email']);
$nombre = trim($_POST['nombre']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$grupo_id = intval($_POST['grupo_id']);

/* Crear usuario */

$stmt = $conn->prepare("
INSERT INTO usuarios (email, nombre, password)
VALUES (?, ?, ?)
");

$stmt->bind_param("sss", $email, $nombre, $password);
$stmt->execute();

$usuario_id = $conn->insert_id;

/* Crear solicitud de grupo */

$stmt2 = $conn->prepare("
INSERT INTO solicitudes_grupo (usuario_id, grupo_id, estado)
VALUES (?, ?, 'pendiente')
");

$stmt2->bind_param("ii", $usuario_id, $grupo_id);
$stmt2->execute();

/* Redirigir */

header("Location: login.php?registro=ok");
exit;