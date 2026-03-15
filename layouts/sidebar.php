<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/permisos.php';

?>

<div class="bg-dark text-white d-flex flex-column vh-100 p-3" style="width: 250px;">

    <!-- LOGO -->
    <div class="text-center mb-4">
        <img src="/ligaepica_v2/assets/img/logo.png"
             alt="Liga Épica"
             style="max-width: 120px;">
    </div>

    <ul class="nav nav-pills flex-column mb-auto">

        <li class="nav-item">
            <a href="/ligaepica_v2/modules/dashboard/index.php"
               class="nav-link text-white">
               📊 Dashboard
            </a>
        </li>

        <li>
            <a href="/ligaepica_v2/modules/grupos/explorar.php"
               class="nav-link text-white">
               🌍 Explorar grupos
            </a>
        </li>

        <li>
            <a href="/ligaepica_v2/modules/actividades/index.php"
               class="nav-link text-white">
               🏆 Actividades
            </a>
        </li>

        <li>
            <a href="/ligaepica_v2/modules/actividades/mis_participaciones.php"
               class="nav-link text-white">
               📝 Mis Participaciones
            </a>
        </li>

        <li>
            <a href="/ligaepica_v2/modules/clasificacion/index.php"
               class="nav-link text-white">
               🏅 Clasificación
            </a>
        </li>

        <?php if (esAdmin()): ?>

        <li>
            <a href="/ligaepica_v2/modules/grupos/index.php"
               class="nav-link text-white">
               👥 Gestión de Grupo
            </a>
        </li>

        <li>
            <a href="/ligaepica_v2/modules/grupos/solicitudes.php"
               class="nav-link text-white">
               📩 Solicitudes
            </a>
        </li>

        <?php endif; ?>

        <!-- BOTÓN TUTORIAL -->
        <li>
            <a href="#" onclick="abrirTutorial()"
               class="nav-link text-white">
               ❓ Tutorial
            </a>
        </li>

    </ul>

    <hr>

    <div>
        <a href="/ligaepica_v2/modules/auth/logout.php"
           class="nav-link text-danger">
           🚪 Cerrar sesión
        </a>
    </div>

</div>