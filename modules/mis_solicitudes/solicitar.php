<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$id_usuario = $_SESSION['id'];
$id_actividad = intval($_POST['id_actividad']);

/* Verificar que no exista ya */
$stmtCheck = $conn->prepare("
    SELECT id FROM actividad_participantes
    WHERE id_usuario = ? AND id_actividad = ?
");
$stmtCheck->bind_param("ii", $id_usuario, $id_actividad);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows == 0) {

    $stmt = $conn->prepare("
        INSERT INTO actividad_participantes
        (id_actividad, id_usuario, participo, puntos_ganados, estado)
        VALUES (?, ?, 1, 0, 'pendiente')
    ");

    $stmt->bind_param("ii", $id_actividad, $id_usuario);
    $stmt->execute();
}

header("Location: index.php");
exit;