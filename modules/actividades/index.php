<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';

$usuario_id = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'] ?? 0;
$hoy = date('Y-m-d');

if(!$grupo_activo){
die("No hay grupo activo.");
}

/* TEMPORADA ACTIVA */

$stmtTemp = $conn->prepare("
SELECT id,nombre
FROM temporadas
WHERE activa = 1
LIMIT 1
");

$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();
$temporada = $resTemp->fetch_assoc();

if(!$temporada){
die("No hay temporada activa.");
}

$temporada_id = $temporada['id'];

/* ACTIVIDADES */

$sql="

SELECT a.*, g.nombre as grupo_nombre
FROM actividades a
JOIN grupos g ON a.id_grupo = g.id
WHERE a.id_temporada=?
AND a.id_grupo=?
ORDER BY a.fecha DESC

";

$stmt=$conn->prepare($sql);
$stmt->bind_param("ii",$temporada_id,$grupo_activo);
$stmt->execute();
$resultado=$stmt->get_result();

ob_start();
?>

<style>

@media (max-width:768px){

.table-actividades{
display:none;
}

.actividad-card{
background:#fff;
border-radius:12px;
padding:15px;
margin-bottom:15px;
box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

.actividad-header{
display:flex;
justify-content:space-between;
font-weight:600;
margin-bottom:5px;
}

.actividad-meta{
font-size:14px;
color:#666;
margin-bottom:10px;
}

.actividad-actions .btn{
margin:3px;
}

}

@media (min-width:769px){

.actividad-card{
display:none;
}

}

</style>

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>Actividades</h2>

<?php if(esAdmin()): ?>

<a href="crear.php" class="btn btn-primary">
+ Crear Actividad
</a>

<?php endif; ?>

</div>

<div class="alert alert-info">
Mostrando actividades de:
<strong><?php echo htmlspecialchars($temporada['nombre']); ?></strong>
</div>

<?php if($resultado->num_rows>0): ?>

<?php while($actividad=$resultado->fetch_assoc()): ?>

<?php

$stmtCount=$conn->prepare("
SELECT COUNT(id)
FROM actividad_participantes
WHERE id_actividad=?
");

$stmtCount->bind_param("i",$actividad['id']);
$stmtCount->execute();
$totalParticipantes=$stmtCount->get_result()->fetch_row()[0] ?? 0;

?>

<div class="actividad-card">

<div class="actividad-header">

<span>
📅 <?php echo date('d/m/Y',strtotime($actividad['fecha'])); ?>

<?php if(!empty($actividad['hora'])): ?>
<br><small>🕘 <?php echo substr($actividad['hora'],0,5); ?></small>
<?php endif; ?>

</span>

<?php if($actividad['fecha'] < $hoy): ?>
<span class="badge bg-secondary">🏁 Finalizada</span>
<?php endif; ?>

</div>

<div class="actividad-meta">

🚴 <?php echo htmlspecialchars($actividad['tipo']); ?><br>

⭐ <?php echo $actividad['puntos']; ?> puntos<br>

👥 <?php echo $totalParticipantes; ?> participantes

</div>

<div class="actividad-actions">

<?php if(esAdmin()): ?>

<a href="participantes.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-secondary btn-sm">👥</a>

<a href="editar.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-warning btn-sm">✏</a>

<a href="eliminar.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar esta actividad?');">🗑</a>

<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

<div class="table-responsive table-actividades">

<table class="table table-striped">

<thead>
<tr>
<th>Fecha</th>
<th>Tipo</th>
<th>Grupo</th>
<th>Puntos</th>
<th>Participantes</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php mysqli_data_seek($resultado,0); ?>

<?php while($actividad=$resultado->fetch_assoc()): ?>

<?php

$stmtCount=$conn->prepare("
SELECT COUNT(id)
FROM actividad_participantes
WHERE id_actividad=?
");

$stmtCount->bind_param("i",$actividad['id']);
$stmtCount->execute();
$totalParticipantes=$stmtCount->get_result()->fetch_row()[0] ?? 0;

?>

<tr>

<td>

<?php echo date('d/m/Y',strtotime($actividad['fecha'])); ?>

<?php if(!empty($actividad['hora'])): ?>
<br><small>🕘 <?php echo substr($actividad['hora'],0,5); ?></small>
<?php endif; ?>

</td>

<td><?php echo htmlspecialchars($actividad['tipo']); ?></td>

<td><?php echo htmlspecialchars($actividad['grupo_nombre']); ?></td>

<td><?php echo $actividad['puntos']; ?></td>

<td>
<span class="badge bg-info"><?php echo $totalParticipantes; ?></span>
</td>

<td>

<a href="participantes.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-secondary btn-sm">👥</a>

<a href="editar.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-warning btn-sm">✏</a>

<a href="eliminar.php?id=<?php echo $actividad['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar esta actividad?');">🗑</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>

<?php else: ?>

<p class="text-muted">
No hay actividades registradas para este grupo.
</p>

<?php endif; ?>

<?php
$content=ob_get_clean();
require '../../layouts/app.php';
?>