<?php
require_once __DIR__ . '/../config/database.php';

/* =========================
   AVISO 60 DÍAS
========================= */

$conn->query("
UPDATE grupos
SET estado = 'aviso'
WHERE activo = 1
AND estado = 'activo'
AND ultima_actividad IS NOT NULL
AND ultima_actividad < DATE_SUB(NOW(), INTERVAL 60 DAY)
");

/* =========================
   INACTIVAR 90 DÍAS
========================= */

$conn->query("
UPDATE grupos
SET estado = 'inactivo', activo = 0
WHERE activo = 1
AND ultima_actividad IS NOT NULL
AND ultima_actividad < DATE_SUB(NOW(), INTERVAL 90 DAY)
");