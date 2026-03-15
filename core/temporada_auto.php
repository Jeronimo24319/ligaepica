<?php

require_once __DIR__ . '/../config/database.php';

$anio_actual = date('Y');
$anio_anterior = $anio_actual - 1;

/* Buscar temporada actual */
$stmt = $conn->prepare("SELECT id FROM temporadas WHERE anio = ?");
$stmt->bind_param("i", $anio_actual);
$stmt->execute();
$result = $stmt->get_result();
$temporada_actual = $result->fetch_assoc();

/* Si no existe temporada del año actual */
if (!$temporada_actual) {

    /* Cerrar solo la temporada anterior */
    $stmtCerrar = $conn->prepare("UPDATE temporadas SET activa = 0, cerrada = 1 WHERE anio = ?");
    $stmtCerrar->bind_param("i", $anio_anterior);
    $stmtCerrar->execute();

    /* Crear nueva temporada */
    $nombre = "Temporada " . $anio_actual;

    $insert = $conn->prepare("
        INSERT INTO temporadas (nombre, anio, activa, cerrada)
        VALUES (?, ?, 1, 0)
    ");

    $insert->bind_param("si", $nombre, $anio_actual);
    $insert->execute();

} else {

    /* Asegurar que solo la actual esté activa */
    $stmtDesactivar = $conn->prepare("UPDATE temporadas SET activa = 0 WHERE anio != ?");
    $stmtDesactivar->bind_param("i", $anio_actual);
    $stmtDesactivar->execute();

    $stmtActivar = $conn->prepare("UPDATE temporadas SET activa = 1 WHERE anio = ?");
    $stmtActivar->bind_param("i", $anio_actual);
    $stmtActivar->execute();
}