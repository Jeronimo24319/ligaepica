<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

requiereAdminGrupo();

/* Obtener grupo */
$grupo_activo = isset($_GET['grupo']) ? intval($_GET['grupo']) : $_SESSION['grupo_activo'];
$usuario_actual = $_SESSION['id'];

/* =========================
OBTENER NOMBRE DEL GRUPO
========================= */

$stmtGrupo = $conn->prepare("
SELECT nombre
FROM grupos
WHERE id = ?
");

$stmtGrupo->bind_param("i",$grupo_activo);
$stmtGrupo->execute();

$grupo = $stmtGrupo->get_result()->fetch_assoc();

$nombre_grupo = $grupo['nombre'] ?? "Grupo";

/* =========================
CONTAR MIEMBROS
========================= */

$stmtCount = $conn->prepare("
SELECT COUNT(*) 
FROM ligaepica_grupos
WHERE grupo_id = ?
");

$stmtCount->bind_param("i",$grupo_activo);
$stmtCount->execute();

$total_miembros = $stmtCount->get_result()->fetch_row()[0];

/* =========================
OBTENER MIEMBROS
========================= */

$stmt = $conn->prepare("
SELECT lg.usuario_id, lg.rol_grupo, u.nombre
FROM ligaepica_grupos lg
JOIN usuarios u ON u.id = lg.usuario_id
WHERE lg.grupo_id = ?
ORDER BY u.nombre
");

$stmt->bind_param("i",$grupo_activo);
$stmt->execute();

$usuarios = $stmt->get_result();

/* =========================
OBTENER TOKEN ACTIVO
========================= */

$stmtToken = $conn->prepare("
SELECT token
FROM invitaciones
WHERE id_grupo=? AND activa=1
LIMIT 1
");

$stmtToken->bind_param("i",$grupo_activo);
$stmtToken->execute();

$resToken = $stmtToken->get_result();

$token = null;

if($rowToken = $resToken->fetch_assoc()){
    $token = $rowToken['token'];
}

$link_invitacion = "";

if($token){
$link_invitacion = "https://ligaepicaciclista.com/ligaepica_v2/invitar.php?token=".$token;
}

ob_start();
?>

<h2 class="mb-3">
Miembros del grupo: <?php echo htmlspecialchars($nombre_grupo); ?>
</h2>

<div class="mb-3">

<a href="invitar.php?grupo=<?php echo $grupo_activo; ?>"
class="btn btn-primary">
+ Invitar usuario
</a>

<?php if($token): ?>

<a class="btn btn-success"
href="https://wa.me/?text=<?php echo urlencode('Únete a nuestro grupo '.$nombre_grupo.' 🚴 '.$link_invitacion); ?>">
📲 Invitar por WhatsApp
</a>

<a href="regenerar_invitacion.php?grupo=<?php echo $grupo_activo; ?>"
class="btn btn-warning">
🔄 Regenerar enlace
</a>

<?php else: ?>

<a href="generar_invitacion.php?grupo=<?php echo $grupo_activo; ?>"
class="btn btn-success">
🔗 Generar enlace
</a>

<?php endif; ?>

</div>

<div class="mb-3">
<span class="badge bg-primary">
👥 <?php echo $total_miembros; ?> miembros
</span>
</div>

<div class="card">
<div class="card-body">

<?php if($usuarios->num_rows == 0): ?>

<div class="alert alert-warning">
Este grupo aún no tiene miembros.
</div>

<?php else: ?>

<table class="table table-striped align-middle">

<thead>
<tr>
<th>Usuario</th>
<th>Rol</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php while($u = $usuarios->fetch_assoc()): ?>

<tr>

<td>
<?= htmlspecialchars($u['nombre']) ?>
</td>

<td>

<?php
if($u['rol_grupo'] == "admin"){
echo "<span class='badge bg-danger'>Admin</span>";
}else{
echo "<span class='badge bg-secondary'>Miembro</span>";
}
?>

</td>

<td>

<?php if($u['usuario_id'] != $usuario_actual): ?>

<?php if($u['rol_grupo']=="miembro"): ?>

<a href="rol.php?usuario=<?= $u['usuario_id'] ?>&grupo=<?= $grupo_activo ?>&accion=admin"
class="btn btn-success btn-sm">
Hacer Admin
</a>

<?php else: ?>

<a href="rol.php?usuario=<?= $u['usuario_id'] ?>&grupo=<?= $grupo_activo ?>&accion=miembro"
class="btn btn-warning btn-sm">
Quitar Admin
</a>

<?php endif; ?>

<?php else: ?>

<span class="text-muted">Tú</span>

<?php endif; ?>

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