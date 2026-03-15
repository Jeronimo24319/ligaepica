<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];

/* obtener grupos del usuario */

$stmt = $conn->prepare("
SELECT 
g.id,
g.nombre,
g.tipo,
g.nivel,
lg.rol_grupo

FROM ligaepica_grupos lg

JOIN grupos g ON g.id = lg.grupo_id

WHERE lg.usuario_id = ?
AND g.activo = 1

ORDER BY g.nombre
");

$stmt->bind_param("i",$usuario_id);
$stmt->execute();

$grupos = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Mis grupos</h2>

<div class="card">
<div class="card-body">

<?php if($grupos->num_rows > 0): ?>

<table class="table table-striped align-middle">

<thead>
<tr>
<th>Grupo</th>
<th>Actividad</th>
<th>Nivel</th>
<th>Rol</th>
<th></th>
</tr>
</thead>

<tbody>

<?php while($g = $grupos->fetch_assoc()): ?>

<tr>

<td>
<strong><?php echo htmlspecialchars($g['nombre']); ?></strong>
</td>

<td>
<?php echo htmlspecialchars($g['tipo']); ?>
</td>

<td>
<?php echo htmlspecialchars($g['nivel']); ?>
</td>

<td>

<?php if($g['rol_grupo'] === 'admin'): ?>

<span class="badge bg-danger">Admin</span>

<?php else: ?>

<span class="badge bg-secondary">Miembro</span>

<?php endif; ?>

</td>

<td>

<a href="/ligaepica_v2/modules/auth/establecer_grupo.php?grupo_id=<?php echo $g['id']; ?>"
class="btn btn-sm btn-primary">

Entrar

</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<p class="text-muted">
No perteneces a ningún grupo todavía.
</p>

<?php endif; ?>

</div>
</div>

<?php

$content = ob_get_clean();
require '../../layouts/app.php';
?>