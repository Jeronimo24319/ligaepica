<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if ($_SESSION['rol'] !== 'admin') {
    die("Acceso restringido.");
}

$id = intval($_GET['id']);

/* Obtener actividad y puntos */
$stmt = $conn->prepare("
    SELECT ap.id_usuario, a.puntos
    FROM actividad_participantes ap
    JOIN actividades a ON ap.id_actividad = a.id
    WHERE ap.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if ($data) {

    $puntos = $data['puntos'];

    $update = $conn->prepare("
        UPDATE actividad_participantes
        SET estado = 'aprobado', puntos_ganados = ?
        WHERE id = ?
    ");

    $update->bind_param("ii", $puntos, $id);
    $update->execute();
}

header("Location: index.php");
exit;