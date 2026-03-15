<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$actividad_id = intval($_GET['id']);


/* ===============================
   APROBAR / RECHAZAR
================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$participante_id = intval($_POST['participante_id']);
$accion = $_POST['accion'];

/* OBTENER PUNTOS DE LA ACTIVIDAD */

$stmtPuntos = $conn->prepare("
SELECT puntos, tipo
FROM actividades
WHERE id = ?
LIMIT 1
");

$stmtPuntos->bind_param("i",$actividad_id);
$stmtPuntos->execute();

$resPuntos = $stmtPuntos->get_result();
$actividad = $resPuntos->fetch_assoc();

$puntos_actividad = $actividad['puntos'] ?? 0;
$tipo_actividad = $actividad['tipo'] ?? 'actividad';


/* OBTENER USUARIO DEL PARTICIPANTE */

$stmtUsuario = $conn->prepare("
SELECT id_usuario
FROM actividad_participantes
WHERE id = ?
LIMIT 1
");

$stmtUsuario->bind_param("i",$participante_id);
$stmtUsuario->execute();

$resUsuario = $stmtUsuario->get_result();
$usuario = $resUsuario->fetch_assoc();

$usuario_id = $usuario['id_usuario'] ?? 0;


if ($accion == "aprobar") {

$stmt = $conn->prepare("
UPDATE actividad_participantes
SET estado='aprobado', participo=1, puntos_ganados=?
WHERE id=?
");

$stmt->bind_param("ii",$puntos_actividad,$participante_id);
$stmt->execute();


if($usuario_id){

$mensaje = "Te han aprobado ".$puntos_actividad." puntos en la actividad ".$tipo_actividad;

$stmtNotif = $conn->prepare("
INSERT INTO notificaciones (usuario_id, mensaje, leido, fecha)
VALUES (?, ?, 0, NOW())
");

$stmtNotif->bind_param("is",$usuario_id,$mensaje);
$stmtNotif->execute();

}

}

if ($accion == "rechazar") {

$stmt = $conn->prepare("
UPDATE actividad_participantes
SET estado='rechazado', participo=0, puntos_ganados=0
WHERE id=?
");

$stmt->bind_param("i",$participante_id);
$stmt->execute();

}

header("Location: participantes.php?id=".$actividad_id);
exit;

}


/* ===============================
   LISTAR PARTICIPANTES
================================ */

$stmt = $conn->prepare("
SELECT ap.*, u.nombre
FROM actividad_participantes ap
JOIN usuarios u ON ap.id_usuario = u.id
WHERE ap.id_actividad = ?
ORDER BY u.nombre
");

$stmt->bind_param("i", $actividad_id);
$stmt->execute();
$resultado = $stmt->get_result();

ob_start();
?>

<h2 class="mb-4">Participantes</h2>

<div class="card shadow-sm">
<div class="card-body p-2">

<?php while($row = $resultado->fetch_assoc()): ?>

<div class="card mb-3 shadow-sm">

<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-2">

<h6 class="mb-0">
👤 <?= htmlspecialchars($row['nombre']) ?>
</h6>

<?php
if($row['estado']=="aprobado"){
echo "<span class='badge bg-success'>Aprobado</span>";
}
elseif($row['estado']=="rechazado"){
echo "<span class='badge bg-danger'>Rechazado</span>";
}
else{
echo "<span class='badge bg-warning'>Pendiente</span>";
}
?>

</div>


<div class="mb-3 small text-muted">

<?php
if($row['participo']){
echo "Participó";
}else{
echo "No participó";
}
?>

• ⭐ <?= $row['puntos_ganados'] ?> pts

</div>


<?php if($row['estado']=="pendiente"): ?>

<form method="POST" class="d-grid gap-2">

<input type="hidden" name="participante_id" value="<?= $row['id'] ?>">

<button name="accion" value="aprobar" class="btn btn-success">
✔ Aprobar
</button>

<button name="accion" value="rechazar" class="btn btn-secondary">
Rechazar
</button>

</form>

<?php else: ?>

<div class="text-muted small">
Sin acciones disponibles
</div>

<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

</div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>