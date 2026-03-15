<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$rol = $_SESSION['rol'];

if ($rol !== 'admin' && $rol !== 'superadmin') {
    die("No tienes permisos.");
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT *
FROM actividades
WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actividad = $res->fetch_assoc();

if (!$actividad) {
    die("Actividad no encontrada.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$tipo = trim($_POST['tipo']);
$fecha = $_POST['fecha'];
$puntos = intval($_POST['puntos']);

$stmt = $conn->prepare("
UPDATE actividades
SET tipo=?, fecha=?, puntos=?
WHERE id=?
");

$stmt->bind_param(
"ssii",
$tipo,
$fecha,
$puntos,
$id
);

$stmt->execute();

header("Location: index.php");
exit;

}

ob_start();
?>

<h2 class="mb-4">Editar Actividad</h2>

<div class="card">
<div class="card-body">

<form method="POST">

<div class="mb-3">
<label class="form-label">Tipo</label>
<input type="text" name="tipo" class="form-control"
value="<?= htmlspecialchars($actividad['tipo']) ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Fecha</label>
<input type="date" name="fecha" class="form-control"
value="<?= $actividad['fecha'] ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Puntos</label>
<input type="number" name="puntos" class="form-control"
value="<?= $actividad['puntos'] ?>" required>
</div>

<button class="btn btn-primary">
Actualizar
</button>

<a href="index.php" class="btn btn-secondary">
Cancelar
</a>

</form>

</div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';