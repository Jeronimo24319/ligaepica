<?php
session_start();
require_once "../../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$email = trim($_POST["email"]);
$password = trim($_POST["password"]);

$stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $usuario = $result->fetch_assoc();

    // Permitir varios tipos de contraseña (temporalmente)
    if (
        password_verify($password, $usuario["password"]) ||
        md5($password) === $usuario["password"] ||
        $password === $usuario["password"]
    ) {

        $_SESSION["id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];

        // 🔴 Si es SUPERADMIN entra directo a la app
        if ($usuario["rol"] === "superadmin") {
            header("Location: /ligaepica_v2/");
            exit();
        }

        // 🟡 Si no es superadmin debe elegir grupo
        header("Location: seleccionar_grupo.php");
        exit();
    }
}

header("Location: login.php?error=1");
exit();