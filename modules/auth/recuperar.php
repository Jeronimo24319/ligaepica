<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../../config/database.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$email = trim($_POST['email']);

/* BUSCAR USUARIO */

$stmt = $conn->prepare("
SELECT id
FROM usuarios
WHERE email = ?
LIMIT 1
");

$stmt->bind_param("s",$email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){

$user = $result->fetch_assoc();
$id_usuario = $user['id'];

/* GENERAR TOKEN */

$token = bin2hex(random_bytes(32));

/* FECHA DE EXPIRACIÓN (1 hora) */

$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

/* GUARDAR TOKEN */

$stmtInsert = $conn->prepare("
INSERT INTO password_resets (id_usuario, token, expira, usado)
VALUES (?, ?, ?, 0)
");

$stmtInsert->bind_param("iss",$id_usuario,$token,$expira);
$stmtInsert->execute();

/* CREAR LINK */

$link = "https://ligaepicaciclista.com/ligaepica_v2/modules/auth/reset_password.php?token=".$token;

$mensaje = "Enlace de recuperación generado:<br><br>
<a href='$link'>$link</a>";

}else{

$mensaje = "Si el correo existe en el sistema se generará un enlace.";

}

}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Recuperar contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card mx-auto" style="max-width:420px">
<div class="card-body">

<h4 class="mb-3">Recuperar contraseña</h4>

<?php if($mensaje): ?>
<div class="alert alert-info">
<?= $mensaje ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<button class="btn btn-primary w-100">
Generar enlace
</button>

<a href="login.php" class="btn btn-link w-100">
Volver al login
</a>

</form>

</div>
</div>

</div>

</body>
</html>