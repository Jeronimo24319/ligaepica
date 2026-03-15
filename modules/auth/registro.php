<?php
require_once '../../config/database.php';
session_start();

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

/* comprobar si existe */

$stmt = $conn->prepare("
SELECT id
FROM usuarios
WHERE email=?
LIMIT 1
");

$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows>0){

$error = "Este email ya está registrado.";

}else{

$stmt = $conn->prepare("
INSERT INTO usuarios (nombre,email,password)
VALUES (?,?,?)
");

$stmt->bind_param("sss",$nombre,$email,$password);
$stmt->execute();

$nuevo_usuario_id = $conn->insert_id;

/* LOGIN AUTOMÁTICO */

$_SESSION['id'] = $nuevo_usuario_id;

/* SI HAY INVITACIÓN */

if(isset($_SESSION['token_invitacion'])){

$token = $_SESSION['token_invitacion'];

$stmt = $conn->prepare("
SELECT *
FROM invitaciones
WHERE token=? AND activa=1
LIMIT 1
");

$stmt->bind_param("s",$token);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows>0){

$inv = $res->fetch_assoc();
$grupo_id = $inv['id_grupo'];

$stmt = $conn->prepare("
INSERT IGNORE INTO ligaepica_grupos
(grupo_id,usuario_id,rol_grupo)
VALUES (?,?,'miembro')
");

$stmt->bind_param("ii",$grupo_id,$nuevo_usuario_id);
$stmt->execute();

$stmt = $conn->prepare("
UPDATE invitaciones
SET usos=usos+1,activa=0
WHERE token=?
");

$stmt->bind_param("s",$token);
$stmt->execute();

$_SESSION['grupo_activo']=$grupo_id;

unset($_SESSION['token_invitacion']);

}

}

header("Location: ../dashboard/index.php");
exit;

}

}

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Crear cuenta - Liga Épica</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container">

<div class="row justify-content-center">

<div class="col-md-4 col-sm-12">

<div class="card shadow mt-5">

<div class="card-body">

<h3 class="text-center mb-4">Crear cuenta</h3>

<?php if($error): ?>
<div class="alert alert-danger">
<?php echo $error; ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">Nombre</label>

<input 
type="text" 
name="nombre" 
class="form-control form-control-lg"
placeholder="Tu nombre"
required>

</div>

<div class="mb-3">

<label class="form-label">Email</label>

<input 
type="email" 
name="email" 
class="form-control form-control-lg"
placeholder="tu@email.com"
required>

</div>

<div class="mb-4">

<label class="form-label">Contraseña</label>

<input 
type="password" 
name="password" 
class="form-control form-control-lg"
placeholder="Contraseña"
required>

</div>

<div class="d-grid">

<button type="submit" class="btn btn-primary btn-lg">

Crear cuenta

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</body>
</html>