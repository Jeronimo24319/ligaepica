<?php

function actualizarActividadGrupo($conn, $grupo_id){

    $stmt = $conn->prepare("
    UPDATE grupos
    SET ultima_actividad = NOW()
    WHERE id = ?
    ");

    $stmt->bind_param("i",$grupo_id);
    $stmt->execute();
}