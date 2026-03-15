<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../../config/database.php';

if(!isset($_GET['token'])){
die("Token inválido.");
}

$token = $_GET['token'];

/* BUSCAR TOKEN */

$stmt = $conn->prepare("
SELECT id_usuario
FROM password_resets
WHERE token=? 
AND usado=0
AND expira > NOW()
LIMIT 1
");

$stmt->bind_param("s",$token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
die("El enlace no es válido o ha expirado.");
}

$data = $result->fetch_assoc();
$id_usuario = $data['id_usuario'];

$mensaje = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

/* ACTUALIZAR CONTRASEÑA */

$stmtUpdate = $conn->prepare("
UPDATE usuarios
SET password = ?
WHERE id = ?
");

$stmtUpdate->bind_param("si",$password,$id_usuario);
$stmtUpdate->execute();

/* MARCAR TOKEN COMO USADO */

$stmtUsed = $conn->prepare("
UPDATE password_resets
SET usado = 1
WHERE token = ?
");

$stmtUsed->bind_param("s",$token);
$stmtUsed->execute();

$mensaje = "Contraseña actualizada correctamente.";

}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Nueva contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card mx-auto" style="max-width:420px">
<div class="card-body">

<h4 class="mb-3">Nueva contraseña</h4>

<?php if($mensaje): ?>

<div class="alert alert-success">
<?= $mensaje ?>
</div>

<a href="login.php" class="btn btn-primary w-100">
Ir al login
</a>

<?php else: ?>

<form method="POST">

<div class="mb-3">
<label>Nueva contraseña</label>
<input type="password" name="password" class="form-control" required>
</div>

<button class="btn btn-success w-100">
Actualizar contraseña
</button>

</form>

<?php endif; ?>

</div>
</div>

</div>

</body>
</html>