<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if(!isset($_GET['grupo'])){
header("Location: index.php");
exit;
}

$grupo_id = intval($_GET['grupo']);


/* ===============================
   USUARIOS QUE NO ESTÁN EN EL GRUPO
================================ */

$stmt = $conn->prepare("
SELECT id,nombre,email
FROM usuarios
WHERE id NOT IN (
SELECT usuario_id
FROM ligaepica_grupos
WHERE grupo_id = ?
)
ORDER BY nombre
");

$stmt->bind_param("i",$grupo_id);
$stmt->execute();

$usuarios = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Invitar usuario al grupo</h2>

<div class="card">
<div class="card-body">

<?php if($usuarios->num_rows == 0): ?>

<div class="alert alert-info">
No hay usuarios disponibles para invitar.
</div>

<?php else: ?>

<table class="table table-striped">

<thead>
<tr>
<th>Nombre</th>
<th>Email</th>
<th>Acción</th>
</tr>
</thead>

<tbody>

<?php while($u = $usuarios->fetch_assoc()): ?>

<tr>

<td><?php echo htmlspecialchars($u['nombre']); ?></td>

<td><?php echo htmlspecialchars($u['email']); ?></td>

<td>

<a href="invitar_guardar.php?usuario=<?php echo $u['id']; ?>&grupo=<?php echo $grupo_id; ?>"
class="btn btn-success btn-sm">

Invitar

</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php endif; ?>

</div>
</div>

<?php

$content = ob_get_clean();
require '../../layouts/app.php';
?>