<?php

/* ===============================
   INICIAR SESIÓN SOLO SI NO EXISTE
================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ===============================
   COMPROBAR LOGIN
================================ */

if (!isset($_SESSION['id']) || $_SESSION['id'] <= 0) {

    header("Location: /ligaepica_v2/modules/auth/login.php");
    exit;

}


/* ===============================
   CARGAR GRUPO ACTIVO
================================ */

require_once __DIR__ . "/grupo_activo.php";


/* ===============================
   MOTOR AUTOMÁTICO DE TEMPORADAS
================================ */

require_once __DIR__ . "/temporada_auto.php";