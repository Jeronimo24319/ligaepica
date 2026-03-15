<?php
require_once 'config/database.php';
session_start();

/* ===============================
COMPROBAR TOKEN
=============================== */

if(!isset($_GET['token'])){
die("Invitación inválida.");
}

$token = trim($_GET['token']);


/* ===============================
VALIDAR TOKEN + OBTENER GRUPO
=============================== */

$stmt = $conn->prepare("
SELECT i.*, g.nombre AS nombre_grupo
FROM invitaciones i
JOIN grupos g ON g.id = i.id_grupo
WHERE i.token=?
AND i.activa=1
AND i.usos < i.limite_usos
AND i.fecha_creacion >= NOW() - INTERVAL 2 DAY
LIMIT 1
");

$stmt->bind_param("s",$token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
die("Invitación no válida o caducada.");
}

$inv = $result->fetch_assoc();
$grupo_id = $inv['id_grupo'];
$nombre_grupo = $inv['nombre_grupo'];


/* ===============================
GUARDAR TOKEN EN SESIÓN
=============================== */

$_SESSION['token_invitacion'] = $token;


/* ===============================
SI NO ESTÁ LOGUEADO
=============================== */

if(!isset($_SESSION['id'])){
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Invitación a grupo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container">

<div class="row justify-content-center">

<div class="col-md-4 col-sm-12">

<div class="card shadow mt-5">

<div class="card-body text-center">

<h4 class="mb-3">Te han invitado a un grupo</h4>

<h3 class="mb-4">🚴 <?php echo htmlspecialchars($nombre_grupo); ?></h3>

<p class="text-muted mb-4">
Crea una cuenta para unirte al grupo.
</p>

<div class="d-grid">

<a href="modules/auth/registro.php" class="btn btn-primary btn-lg">
Crear cuenta
</a>

</div>

<br>

<a href="modules/auth/login.php" class="text-decoration-none">
Ya tengo cuenta
</a>

</div>

</div>

</div>

</div>

</div>

</body>
</html>

<?php
exit;
}


/* ===============================
USUARIO LOGUEADO
=============================== */

$usuario_id = $_SESSION['id'];


/* ===============================
AÑADIR USUARIO AL GRUPO
=============================== */

$stmt = $conn->prepare("
INSERT IGNORE INTO ligaepica_grupos
(grupo_id, usuario_id, rol_grupo)
VALUES (?, ?, 'miembro')
");

$stmt->bind_param("ii",$grupo_id,$usuario_id);
$stmt->execute();


/* ===============================
ACTUALIZAR USOS DEL TOKEN
=============================== */

$stmt = $conn->prepare("
UPDATE invitaciones
SET usos = usos + 1
WHERE token=?
");

$stmt->bind_param("s",$token);
$stmt->execute();


/* ===============================
DESACTIVAR SI LLEGÓ AL LÍMITE
=============================== */

$stmt = $conn->prepare("
UPDATE invitaciones
SET activa = 0
WHERE token=?
AND usos >= limite_usos
");

$stmt->bind_param("s",$token);
$stmt->execute();


/* ===============================
ACTIVAR GRUPO
=============================== */

$_SESSION['grupo_activo'] = $grupo_id;


/* ===============================
LIMPIAR TOKEN
=============================== */

unset($_SESSION['token_invitacion']);


/* ===============================
REDIRIGIR
=============================== */

header("Location: modules/dashboard/index.php");
exit;
?>