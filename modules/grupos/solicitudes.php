<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$rol = $_SESSION['rol'];
$grupo_activo = $_SESSION['grupo_activo'];

if($rol !== 'admin' && $rol !== 'superadmin'){
die("No tienes permisos.");
}

/* ===============================
   OBTENER SOLICITUDES DEL GRUPO
================================ */

$sql = "
SELECT 
s.id,
u.nombre,
u.email,
g.nombre AS grupo,
s.fecha_solicitud

FROM solicitudes_grupo s

JOIN usuarios u ON u.id = s.usuario_id
JOIN grupos g ON g.id = s.grupo_id

WHERE s.estado = 'pendiente'
AND s.grupo_id = ?

ORDER BY s.fecha_solicitud ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$grupo_activo);
$stmt->execute();

$solicitudes = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Solicitudes de acceso</h2>

<div class="card">
<div class="card-body">

<?php if($solicitudes->num_rows > 0): ?>

<table class="table table-striped align-middle">

<thead>
<tr>
<th>Usuario</th>
<th>Email</th>
<th>Grupo</th>
<th>Fecha</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php while($s = $solicitudes->fetch_assoc()): ?>

<tr>

<td>
<?php echo htmlspecialchars($s['nombre']); ?>
</td>

<td>
<?php echo htmlspecialchars($s['email']); ?>
</td>

<td>
<?php echo htmlspecialchars($s['grupo']); ?>
</td>

<td>
<?php echo date("d/m/Y H:i",strtotime($s['fecha_solicitud'])); ?>
</td>

<td>

<a class="btn btn-success btn-sm"
href="aprobar_solicitud.php?id=<?php echo $s['id']; ?>">

Aceptar

</a>

<a class="btn btn-danger btn-sm"
href="rechazar_solicitud.php?id=<?php echo $s['id']; ?>">

Rechazar

</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<p class="text-muted">
No hay solicitudes pendientes.
</p>

<?php endif; ?>

</div>
</div>

<?php

$content = ob_get_clean();
require '../../layouts/app.php';
?>