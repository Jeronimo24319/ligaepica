<?php

require_once __DIR__ . '/../config/database.php';

$anio_actual = date("Y");

/* temporada activa */
$sql = "SELECT * FROM temporadas WHERE activa = 1 LIMIT 1";
$result = $conn->query($sql);
$temporada = $result->fetch_assoc();

if(!$temporada) return;

$anio_temporada = $temporada['anio'];

if($anio_actual > $anio_temporada){

    /* cerrar temporada anterior */
    $conn->query("UPDATE temporadas SET activa = 0, cerrada = 1 WHERE id = ".$temporada['id']);

    /* activar nueva temporada si existe */
    $sql = "SELECT * FROM temporadas WHERE anio = $anio_actual LIMIT 1";
    $result = $conn->query($sql);

    if($result->num_rows > 0){

        $nueva = $result->fetch_assoc();

        $conn->query("UPDATE temporadas SET activa = 1 WHERE id = ".$nueva['id']);

    }else{

        /* crear nueva temporada */
        $conn->query("
        INSERT INTO temporadas (nombre, anio, activa, cerrada)
        VALUES ('Temporada $anio_actual', $anio_actual, 1, 0)
        ");

    }

    /* preparar siguiente temporada */
    $anio_siguiente = $anio_actual + 1;

    $sql = "SELECT id FROM temporadas WHERE anio = $anio_siguiente";
    $result = $conn->query($sql);

    if($result->num_rows == 0){

        $conn->query("
        INSERT INTO temporadas (nombre, anio, activa, cerrada)
        VALUES ('Temporada $anio_siguiente', $anio_siguiente, 0, 0)
        ");

    }

}