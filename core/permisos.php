<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   SUPERADMIN
================================ */

function esSuperAdmin(){
    return ($_SESSION['rol'] ?? '') === 'superadmin';
}


/* ===============================
   ADMIN DE GRUPO (CONSULTANDO BD)
================================ */

function esAdminGrupo(){

    global $conn;

    if(empty($_SESSION['grupo_activo']) || empty($_SESSION['id'])){
        return false;
    }

    $grupo_id = $_SESSION['grupo_activo'];
    $usuario_id = $_SESSION['id'];

    $stmt = $conn->prepare("
        SELECT rol_grupo
        FROM ligaepica_grupos
        WHERE grupo_id=? AND usuario_id=?
        LIMIT 1
    ");

    $stmt->bind_param("ii",$grupo_id,$usuario_id);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();

    return $res && $res['rol_grupo'] === 'admin';
}


/* ===============================
   ADMIN GLOBAL (SUPER O GRUPO)
================================ */

function esAdmin(){
    return esSuperAdmin() || esAdminGrupo();
}


/* ===============================
   REQUIERE ADMIN
================================ */

function requiereAdmin(){

    if(!esAdmin()){
        die("No tienes permisos.");
    }

}


/* ===============================
   REQUIERE ADMIN DE GRUPO
================================ */

function requiereAdminGrupo(){

    if(!esAdmin()){
        die("No tienes permisos para gestionar este grupo.");
    }

}


/* ===============================
   REQUIERE SUPERADMIN
================================ */

function requiereSuperAdmin(){

    if(!esSuperAdmin()){
        die("Acceso restringido.");
    }

}