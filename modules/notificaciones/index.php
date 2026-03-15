<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'];

$stmt = $conn->prepare("
SELECT *
FROM notificaciones
WHERE usuario_id=?
ORDER BY fecha DESC
");

$stmt->bind_param("i",$usuario_id);
$stmt->execute();

$notificaciones = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Notificaciones</h2>

<div class="card">
<div class="card-body">

<table class="table table-striped">

<thead>
<tr>
<th>Mensaje</th>
<th>Fecha</th>
</tr>
</thead>

<tbody>

<?php while($n=$notificaciones->fetch_assoc()): ?>

<tr>

<td>
<?= htmlspecialchars($n['mensaje']) ?>
</td>

<td>
<?= date("d/m/Y H:i",strtotime($n['fecha'])) ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';