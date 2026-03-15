<?php

function procesarPuntuacionesAutomaticas($conn){

$stmt = $conn->prepare("
UPDATE actividad_participantes ap
JOIN actividades a ON a.id = ap.id_actividad

SET
ap.estado = 'aprobado',
ap.puntos_ganados = a.puntos

WHERE
ap.participo = 1
AND ap.estado = 'pendiente'
AND NOW() > CONCAT(a.fecha,' ',a.hora_fin)
");

$stmt->execute();

}