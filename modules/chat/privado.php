<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id  = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'];

/* ===============================
   USUARIOS DEL GRUPO
=============================== */

$stmtUsuarios = $conn->prepare("
SELECT id,nombre
FROM usuarios
WHERE id != ?
ORDER BY nombre
");

$stmtUsuarios->bind_param("i",$usuario_id);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->get_result();

/* ===============================
   CONVERSACIONES EXISTENTES
=============================== */

$stmt = $conn->prepare("
SELECT 
    c.id,
    CASE 
        WHEN c.usuario1 = ? THEN u2.nombre
        ELSE u1.nombre
    END as otro_usuario,

    MAX(m.fecha) as ultima_fecha,

    SUBSTRING_INDEX(MAX(CONCAT(m.fecha,'||',m.mensaje)),'||',-1) as ultimo_mensaje,

    (
        SELECT COUNT(*)
        FROM mensajes_privados mp
        LEFT JOIN mensajes_privados_leidos ml
        ON ml.mensaje_id = mp.id
        AND ml.usuario_id = ?
        WHERE mp.conversacion_id = c.id
        AND mp.remitente_id != ?
        AND ml.id IS NULL
    ) as no_leidos

FROM conversaciones_privadas c
JOIN usuarios u1 ON c.usuario1 = u1.id
JOIN usuarios u2 ON c.usuario2 = u2.id
LEFT JOIN mensajes_privados m ON m.conversacion_id = c.id

WHERE c.grupo_id = ?
AND (c.usuario1 = ? OR c.usuario2 = ?)

GROUP BY c.id
ORDER BY ultima_fecha DESC
");

$stmt->bind_param(
"iiiiii",
$usuario_id,
$usuario_id,
$usuario_id,
$grupo_activo,
$usuario_id,
$usuario_id
);

$stmt->execute();
$conversaciones = $stmt->get_result();

ob_start();
?>

<h4 class="mb-4">👤 Chats Privados</h4>

<div class="row">

<div class="col-lg-5">

<div class="card shadow-sm mb-4">

<div class="card-header bg-success text-white">
Iniciar conversación
</div>

<div class="card-body p-0">

<ul class="list-group list-group-flush">

<?php while($u=$usuarios->fetch_assoc()): ?>

<li class="list-group-item d-flex justify-content-between align-items-center">

<?= htmlspecialchars($u['nombre']) ?>

<a href="abrir_chat.php?usuario=<?= $u['id'] ?>"
class="btn btn-sm btn-success">
💬 Mensaje
</a>

</li>

<?php endwhile; ?>

</ul>

</div>
</div>

</div>


<div class="col-lg-7">

<div class="card shadow-sm">

<div class="card-header bg-primary text-white">
Conversaciones
</div>

<div class="card-body p-0">

<?php if ($conversaciones->num_rows > 0): ?>

<ul class="list-group list-group-flush">

<?php while ($conv = $conversaciones->fetch_assoc()): ?>

<li class="list-group-item d-flex justify-content-between align-items-center">

<a href="conversacion.php?id=<?= $conv['id'] ?>"
style="text-decoration:none;color:inherit;flex-grow:1;">

<strong><?= htmlspecialchars($conv['otro_usuario']) ?></strong>

<br>

<small class="text-muted">
<?= htmlspecialchars($conv['ultimo_mensaje'] ?? 'Sin mensajes aún') ?>
</small>

</a>

<?php if ($conv['no_leidos'] > 0): ?>

<span class="badge bg-danger rounded-pill">
<?= $conv['no_leidos'] ?>
</span>

<?php endif; ?>

</li>

<?php endwhile; ?>

</ul>

<?php else: ?>

<div class="p-3">
<p class="text-muted">No tienes conversaciones aún.</p>
</div>

<?php endif; ?>

</div>
</div>

</div>

</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';