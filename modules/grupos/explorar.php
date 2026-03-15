<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];

/* ===============================
   TEMPORADA ACTIVA
================================ */

$stmtTemp = $conn->prepare("
SELECT id 
FROM temporadas 
WHERE activa = 1 
LIMIT 1
");

$stmtTemp->execute();
$temporada = $stmtTemp->get_result()->fetch_assoc();

if(!$temporada){
die("No hay temporada activa");
}

$temporada_id = $temporada['id'];


/* ===============================
   OBTENER GRUPOS
================================ */

$stmt = $conn->prepare("
SELECT 
g.id,
g.nombre,
g.tipo,
g.nivel,
g.ubicacion,
g.tipo_acceso,

(SELECT COUNT(*) 
 FROM ligaepica_grupos lg 
 WHERE lg.grupo_id = g.id) AS miembros,

(SELECT COUNT(*) 
 FROM ligaepica_grupos lg 
 WHERE lg.grupo_id = g.id 
 AND lg.usuario_id = ?) AS ya_miembro

FROM grupos g

WHERE g.temporada_id = ?
AND g.activo = 1

ORDER BY g.nombre
");

$stmt->bind_param("ii",$usuario_id,$temporada_id);
$stmt->execute();

$grupos = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Explorar grupos</h2>

<div class="card">
<div class="card-body">

<?php if($grupos->num_rows > 0): ?>

<table class="table table-striped align-middle">

<thead>
<tr>
<th>Grupo</th>
<th>Actividad</th>
<th>Nivel</th>
<th>Ubicación</th>
<th>Miembros</th>
<th>Acceso</th>
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
<?php echo htmlspecialchars($g['ubicacion']); ?>
</td>

<td>
<?php echo $g['miembros']; ?>
</td>

<td>

<?php if($g['tipo_acceso'] === 'publico'): ?>

<span class="badge bg-success">Público</span>

<?php elseif($g['tipo_acceso'] === 'privado'): ?>

<span class="badge bg-warning">Privado</span>

<?php else: ?>

<span class="badge bg-dark">Invitación</span>

<?php endif; ?>

</td>

<td>

<?php if($g['ya_miembro'] > 0): ?>

<span class="badge bg-secondary">
Ya perteneces
</span>

<?php else: ?>

<?php if($g['tipo_acceso'] === 'publico'): ?>

<a href="entrar.php?grupo=<?php echo $g['id']; ?>"
class="btn btn-sm btn-success">

Entrar

</a>

<?php elseif($g['tipo_acceso'] === 'privado'): ?>

<a href="solicitar.php?grupo=<?php echo $g['id']; ?>"
class="btn btn-sm btn-warning">

Solicitar

</a>

<?php else: ?>

<span class="text-muted">
Solo invitación
</span>

<?php endif; ?>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<p class="text-muted">No hay grupos disponibles.</p>

<?php endif; ?>

</div>
</div>

<?php

$content = ob_get_clean();
require '../../layouts/app.php';

?>