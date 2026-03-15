<?php
session_start();

if (isset($_SESSION["id"])) {
    header("Location: /ligaepica_v2/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<title>Login - Liga Épica</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
height:100vh;
margin:0;
display:flex;
align-items:center;
justify-content:center;
background:linear-gradient(135deg,#0d6efd,#6610f2);
font-family:Arial, sans-serif;
}

.login-card{
width:100%;
max-width:420px;
border-radius:15px;
box-shadow:0 20px 40px rgba(0,0,0,0.2);
}

.logo{
font-size:32px;
font-weight:bold;
text-align:center;
margin-bottom:20px;
color:#0d6efd;
}

.form-control{
padding:12px;
border-radius:10px;
}

.btn-login{
padding:12px;
font-size:18px;
border-radius:10px;
}

.links{
text-align:center;
margin-top:15px;
}

.links a{
text-decoration:none;
font-size:14px;
}

.links a:hover{
text-decoration:underline;
}

</style>

</head>

<body>

<div class="card login-card p-4">

<div class="logo">
🏆 Liga Épica
</div>

<h4 class="text-center mb-4">Iniciar sesión</h4>

<form action="procesar_login.php" method="POST">

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Contraseña</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" class="btn btn-primary w-100 btn-login">
Entrar
</button>

</form>

<div class="links">

<a href="registro.php">Crear cuenta</a>
<br>

<a href="recuperar.php">¿Olvidaste tu contraseña?</a>

</div>

</div>

</body>
</html>