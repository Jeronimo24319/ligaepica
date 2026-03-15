<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: /ligaepica_v2/modules/auth/login.php");
    exit;
}

$usuario_id = $_SESSION['id'];

/* ================================
   DATOS DEL USUARIO
================================ */

$stmt = $conn->prepare("
    SELECT u.nombre, u.email, u.id_grupo, g.nombre AS grupo_nombre
    FROM usuarios u
    LEFT JOIN grupos g ON u.id_grupo = g.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

/* ================================
   TEMPORADA ACTIVA
================================ */

$temp = $conn->query("SELECT * FROM temporadas WHERE activa = 1 LIMIT 1");
$temporadaActiva = $temp->fetch_assoc();

/* ================================
   PUNTOS ACTUALES
================================ */

$stmt = $conn->prepare("
    SELECT IFNULL(SUM(ap.puntos_ganados),0) AS total
    FROM actividad_participantes ap
    INNER JOIN actividades a ON ap.id_actividad = a.id
    WHERE ap.id_usuario = ?
    AND ap.estado = 'aprobado'
    AND a.id_temporada = ?
");

$stmt->bind_param("ii", $usuario_id, $temporadaActiva['id']);
$stmt->execute();
$puntosActuales = $stmt->get_result()->fetch_assoc()['total'];

/* ================================
   POSICIÓN ACTUAL
================================ */

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

$stmt->bind_param("i", $temporadaActiva['id']);
$stmt->execute();
$ranking = $stmt->get_result();

$posicion = 1;
$miPosicion = "-";

while ($row = $ranking->fetch_assoc()) {
    if ($row['id'] == $usuario_id) {
        $miPosicion = $posicion;
        break;
    }
    $posicion++;
}

/* ================================
   HISTÓRICO SIN TEMPORADAS FUTURAS
================================ */

$historico = $conn->query("
    SELECT t.nombre, t.id
    FROM temporadas t
    WHERE t.anio <= YEAR(CURDATE())
    ORDER BY t.anio DESC
");

/* ================================
   INSIGNIAS HISTÓRICAS
================================ */

$stmtIns = $conn->prepare("
    SELECT 
        i.nombre,
        t.nombre AS temporada_nombre,
        t.anio
    FROM usuario_insignias ui
    INNER JOIN insignias i ON ui.id_insignia = i.id
    INNER JOIN temporadas t ON ui.temporada_id = t.id
    WHERE ui.id_usuario = ?
    ORDER BY t.anio DESC
");

$stmtIns->bind_param("i", $usuario_id);
$stmtIns->execute();
$insignias = $stmtIns->get_result();

$insigniasPorTemporada = [];

while ($row = $insignias->fetch_assoc()) {
    $insigniasPorTemporada[$row['temporada_nombre']][] = $row;
}

ob_start();
?>

<h2 class="mb-4">👤 Mi Perfil</h2>

<div class="card shadow-sm">
<div class="card-body text-center">

<h4 class="mb-1"><?= htmlspecialchars($usuario['nombre']) ?></h4>
<p class="text-muted mb-3">📧 <?= htmlspecialchars($usuario['email']) ?></p>

<p class="mb-1">
🏁 <strong><?= htmlspecialchars($temporadaActiva['nombre']) ?></strong>
</p>

<p class="mb-1">
⭐ <?= $puntosActuales ?> puntos
</p>

<p class="mb-2">
🏅 Posición <?= $miPosicion ?>º
</p>

<?php if($usuario['grupo_nombre']): ?>
<p class="text-muted small">
Grupo: <?= htmlspecialchars($usuario['grupo_nombre']) ?>
</p>
<?php endif; ?>

</div>
</div>


<div class="card mt-4 shadow-sm">
<div class="card-body">

<h4 class="mb-3">📊 Histórico</h4>

<ul class="list-group list-group-flush">

<?php while ($row = $historico->fetch_assoc()): ?>

<li class="list-group-item">
🏁 <?= htmlspecialchars($row['nombre']) ?>
</li>

<?php endwhile; ?>

</ul>

</div>
</div>


<?php if (!empty($insigniasPorTemporada)): ?>

<div class="card mt-4 shadow-sm">
<div class="card-body">

<h4 class="mb-4">🏆 Palmarés</h4>

<?php foreach ($insigniasPorTemporada as $temporada => $lista): ?>

<h5 class="border-bottom pb-2 mb-3">
<?= htmlspecialchars($temporada) ?>
</h5>

<div class="row">

<?php foreach ($lista as $ins): 

$bg = "bg-light";
$icono = "🏅";
$border = "";

if (strpos($ins['nombre'], 'Campeón') !== false) {
$icono = "🥇";
$bg = "bg-warning bg-opacity-25";
$border = "border-warning";
}
elseif (strpos($ins['nombre'], 'Subcampeón') !== false) {
$icono = "🥈";
$bg = "bg-secondary bg-opacity-25";
$border = "border-secondary";
}
elseif (strpos($ins['nombre'], 'Tercer') !== false) {
$icono = "🥉";
$bg = "bg-danger bg-opacity-25";
$border = "border-danger";
}
?>

<div class="col-md-4 mb-3">

<div class="card text-center shadow-sm <?= $bg ?> <?= $border ?>">

<div class="card-body">

<div style="font-size:45px;">
<?= $icono ?>
</div>

<h6 class="mt-2 mb-0">
<?= htmlspecialchars($ins['nombre']) ?>
</h6>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endforeach; ?>

</div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . "/../../layouts/app.php";
?>