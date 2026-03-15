<?php

// ============================================
// 🔒 SOLO PERMITIR EJECUCIÓN DESDE CRON (CLI)
// ============================================

if (php_sapi_name() !== 'cli') {
    exit("Acceso no permitido.\n");
}

require_once __DIR__ . "/../config/database.php";

/* ========================================
   1️⃣ OBTENER TEMPORADA ACTIVA
======================================== */

$result = $conn->query("SELECT * FROM temporadas WHERE activa = 1 LIMIT 1");

if (!$result || $result->num_rows === 0) {
    exit("No hay temporada activa.\n");
}

$temporada = $result->fetch_assoc();
$temporada_id = $temporada["id"];
$anioActual   = (int)$temporada["anio"];
$anioNuevo    = $anioActual + 1;

/* ========================================
   2️⃣ CALCULAR RANKING FINAL
======================================== */

$stmt = $conn->prepare("
    SELECT u.id,
           IFNULL(SUM(ap.puntos_ganados),0) AS total
    FROM usuarios u
    LEFT JOIN actividad_participantes ap 
        ON u.id = ap.id_usuario 
        AND ap.estado = 'aprobado'
    LEFT JOIN actividades a 
        ON ap.id_actividad = a.id 
        AND a.id_temporada = ?
    GROUP BY u.id
    ORDER BY total DESC
");

$stmt->bind_param("i", $temporada_id);
$stmt->execute();
$ranking = $stmt->get_result();

$posicion = 1;

while ($row = $ranking->fetch_assoc()) {

    $insignia_nombre = null;

    if ($posicion == 1) {
        $insignia_nombre = 'Campeón de Temporada';
    } elseif ($posicion == 2) {
        $insignia_nombre = 'Subcampeón de Temporada';
    } elseif ($posicion == 3) {
        $insignia_nombre = 'Tercer Puesto de Temporada';
    }

    if ($insignia_nombre) {

        $stmt2 = $conn->prepare("SELECT id FROM insignias WHERE nombre = ? LIMIT 1");
        $stmt2->bind_param("s", $insignia_nombre);
        $stmt2->execute();
        $insignia = $stmt2->get_result()->fetch_assoc();

        if ($insignia) {

            $stmt3 = $conn->prepare("
                INSERT INTO usuario_insignias 
                (id_usuario, id_insignia, temporada_id)
                VALUES (?, ?, ?)
            ");

            $stmt3->bind_param("iii", $row["id"], $insignia["id"], $temporada_id);
            $stmt3->execute();
        }
    }

    if ($posicion >= 3) break;

    $posicion++;
}

/* ========================================
   3️⃣ CERRAR TEMPORADA ACTUAL
======================================== */

$stmt = $conn->prepare("
    UPDATE temporadas 
    SET activa = 0,
        cerrada = 1
    WHERE id = ?
");
$stmt->bind_param("i", $temporada_id);
$stmt->execute();

/* ========================================
   4️⃣ CREAR NUEVA TEMPORADA
======================================== */

$nombreNueva = "Temporada " . $anioNuevo;

$stmt = $conn->prepare("
    INSERT INTO temporadas (nombre, anio, activa, cerrada)
    VALUES (?, ?, 1, 0)
");
$stmt->bind_param("si", $nombreNueva, $anioNuevo);
$stmt->execute();

echo "Temporada cerrada y nueva creada correctamente.\n";