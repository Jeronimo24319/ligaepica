<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';
requiereAdmin();

/* Obtener temporada activa */
$stmtTemp = $conn->prepare("SELECT id, nombre FROM temporadas WHERE activa = 1 LIMIT 1");
$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();
$temporada = $resTemp->fetch_assoc();

if (!$temporada) {
    die("No hay temporada activa.");
}

$temporada_id = intval($temporada['id']);

/* CONTADORES SOLO DE TEMPORADA ACTIVA */
$total = $conn->query("SELECT COUNT(*) as total FROM grupos WHERE temporada_id = $temporada_id")->fetch_assoc()['total'];
$activos = $conn->query("SELECT COUNT(*) as total FROM grupos WHERE temporada_id = $temporada_id AND activo = 1")->fetch_assoc()['total'];
$inactivos = $conn->query("SELECT COUNT(*) as total FROM grupos WHERE temporada_id = $temporada_id AND activo = 0")->fetch_assoc()['total'];

/* FILTRO */
$filtro = $_GET['filtro'] ?? 'todos';
$where = "WHERE temporada_id = $temporada_id";

if ($filtro === 'activos') {
    $where .= " AND activo = 1";
} elseif ($filtro === 'inactivos') {
    $where .= " AND activo = 0";
}

$sql = "SELECT id, nombre, estado, activo, tipo_acceso FROM grupos $where ORDER BY id DESC";
$resultado = $conn->query($sql);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
<h2>Gestión de Grupos</h2>
<a href="crear.php" class="btn btn-primary">
+ Crear Grupo
</a>
</div>

<div class="alert alert-info">
Mostrando grupos de: <strong><?php echo htmlspecialchars($temporada['nombre']); ?></strong>
</div>

<div class="row mb-4">

<div class="col-md-4 col-4">
<div class="card text-center">
<div class="card-body">
<h6 class="text-muted">Total</h6>
<h3><?php echo $total; ?></h3>
</div>
</div>
</div>

<div class="col-md-4 col-4">
<div class="card text-center border-success">
<div class="card-body">
<h6 class="text-success">Activos</h6>
<h3><?php echo $activos; ?></h3>
</div>
</div>
</div>

<div class="col-md-4 col-4">
<div class="card text-center border-secondary">
<div class="card-body">
<h6 class="text-secondary">Inactivos</h6>
<h3><?php echo $inactivos; ?></h3>
</div>
</div>
</div>

</div>

<div class="mb-3">

<a href="index.php"
class="btn btn-sm <?php echo ($filtro === 'todos') ? 'btn-dark' : 'btn-outline-dark'; ?>">
Todos
</a>

<a href="index.php?filtro=activos"
class="btn btn-sm <?php echo ($filtro === 'activos') ? 'btn-success' : 'btn-outline-success'; ?>">
Activos
</a>

<a href="index.php?filtro=inactivos"
class="btn btn-sm <?php echo ($filtro === 'inactivos') ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
Inactivos
</a>

</div>

<?php if ($resultado && $resultado->num_rows > 0): ?>

<!-- ============================= -->
<!-- VISTA MOVIL (TARJETAS) -->
<!-- ============================= -->

<div class="d-md-none">

<?php
$resultado->data_seek(0);
while ($grupo = $resultado->fetch_assoc()):
?>

<div class="card mb-3 shadow-sm">

<div class="card-body">

<h5 class="mb-2">
<?php echo htmlspecialchars($grupo['nombre']); ?>
</h5>

<p class="mb-1">
Estado: <strong><?php echo $grupo['estado']; ?></strong>
</p>

<p class="mb-2">
Activo:
<?php if ($grupo['activo']): ?>
<span class="badge bg-success">Sí</span>
<?php else: ?>
<span class="badge bg-secondary">No</span>
<?php endif; ?>
</p>

<div class="d-flex flex-wrap gap-2">

<a href="miembros.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-info">
👥
</a>

<a href="editar.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-warning">
✏
</a>

<?php if($grupo['tipo_acceso'] === 'publico'): ?>

<a href="entrar.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-success">
Entrar
</a>

<?php elseif($grupo['tipo_acceso'] === 'privado'): ?>

<a href="solicitar.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-warning">
Solicitar
</a>

<?php else: ?>

<span class="badge bg-dark">
Solo invitación
</span>

<?php endif; ?>

<?php if ($grupo['activo']): ?>

<a href="toggle.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-danger">
🔴
</a>

<?php else: ?>

<a href="toggle.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-success">
🟢
</a>

<?php endif; ?>

</div>

</div>
</div>

<?php endwhile; ?>

</div>


<!-- ============================= -->
<!-- VISTA ESCRITORIO (TABLA) -->
<!-- ============================= -->

<div class="card d-none d-md-block">
<div class="card-body">

<table class="table table-striped align-middle">

<thead>
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Estado</th>
<th>Activo</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php
$resultado->data_seek(0);
while ($grupo = $resultado->fetch_assoc()):
?>

<tr>

<td><?php echo $grupo['id']; ?></td>

<td><?php echo htmlspecialchars($grupo['nombre']); ?></td>

<td><?php echo $grupo['estado']; ?></td>

<td>
<?php if ($grupo['activo']): ?>
<span class="badge bg-success">Sí</span>
<?php else: ?>
<span class="badge bg-secondary">No</span>
<?php endif; ?>
</td>

<td>

<a href="miembros.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-info">
👥 Miembros
</a>

<a href="editar.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-warning">
✏
</a>

<?php if($grupo['tipo_acceso'] === 'publico'): ?>

<a href="entrar.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-success">
Entrar
</a>

<?php elseif($grupo['tipo_acceso'] === 'privado'): ?>

<a href="solicitar.php?grupo=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-warning">
Solicitar
</a>

<?php else: ?>

<span class="badge bg-dark">
Solo invitación
</span>

<?php endif; ?>

<?php if ($grupo['activo']): ?>

<a href="toggle.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-danger">
🔴
</a>

<?php else: ?>

<a href="toggle.php?id=<?php echo $grupo['id']; ?>"
class="btn btn-sm btn-success">
🟢
</a>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

<?php else: ?>

<p class="text-muted">No hay grupos para esta temporada.</p>

<?php endif; ?>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>