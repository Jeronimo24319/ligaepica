<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'];

$stmt = $conn->prepare("
    SELECT ap.puntos_ganados, ap.estado,
           a.tipo, 
           a.fecha
    FROM actividad_participantes ap
    JOIN actividades a ON ap.id_actividad = a.id
    WHERE ap.id_usuario = ?
    AND a.id_grupo = ?
    ORDER BY a.fecha DESC
");

$stmt->bind_param("ii", $usuario_id, $grupo_activo);
$stmt->execute();
$resultado = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">📝 Mis Participaciones</h2>

<div class="card">
<div class="card-body">

<?php if ($resultado->num_rows > 0): ?>

<table class="table table-striped align-middle">
<thead>
<tr>
<th>Actividad</th>
<th>Fecha</th>
<th>Puntos</th>
<th>Estado</th>
</tr>
</thead>
<tbody>

<?php while ($row = $resultado->fetch_assoc()): ?>

<tr>
<td><?= htmlspecialchars($row['tipo']) ?></td>
<td><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>
<td><?= $row['puntos_ganados'] ?></td>
<td>
<?php if ($row['estado'] === 'aprobado'): ?>
<span class="badge bg-success">Aprobado</span>
<?php else: ?>
<span class="badge bg-danger">Rechazado</span>
<?php endif; ?>
</td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>

<p class="text-muted">No tienes participaciones aún.</p>

<?php endif; ?>

</div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';