<?php
require_once "../../core/auth.php";
require_once "../../config/database.php";

$grupo_activo = $_SESSION['grupo_activo'];
$mi_id = $_SESSION['id'];

/* ===============================
   TEMPORADA ACTIVA
================================ */

$stmtTemp = $conn->prepare("
SELECT id, nombre
FROM temporadas
WHERE activa = 1
LIMIT 1
");

$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();

if ($resTemp->num_rows === 0) {
    die("No hay temporada activa.");
}

$temporada = $resTemp->fetch_assoc();
$temporada_id = $temporada['id'];

/* ===============================
   CLASIFICACIÓN
================================ */

$stmt = $conn->prepare("
SELECT 
    u.id,
    u.nombre,
    COALESCE(SUM(ap.puntos_ganados),0) AS total

FROM ligaepica_grupos lg

INNER JOIN usuarios u 
ON lg.usuario_id = u.id

LEFT JOIN actividad_participantes ap 
ON u.id = ap.id_usuario
AND ap.estado = 'aprobado'

LEFT JOIN actividades a 
ON a.id = ap.id_actividad
AND a.id_temporada = ?
AND a.id_grupo = ?

WHERE lg.grupo_id = ?

GROUP BY u.id, u.nombre
ORDER BY total DESC
");

$stmt->bind_param("iii", $temporada_id, $grupo_activo, $grupo_activo);
$stmt->execute();

$result = $stmt->get_result();

/* ===============================
   GUARDAR RANKING
================================ */

$ranking = [];

while ($row = $result->fetch_assoc()) {
    $ranking[] = $row;
}

/* ===============================
   PUNTOS MÁXIMOS (LÍDER)
================================ */

$maxPuntos = 0;

foreach ($ranking as $r) {
    if ($r['total'] > $maxPuntos) {
        $maxPuntos = $r['total'];
    }
}

ob_start();
?>

<style>

.progress{
height:18px;
background:#eee;
border-radius:10px;
overflow:hidden;
}

.progress-bar{
transition:width 1s ease;
}

/* ORO */

.bar-gold{
background:linear-gradient(90deg,#ffd700,#ffb400,#ffd700);
position:relative;
overflow:hidden;
}

.bar-gold::after{
content:'';
position:absolute;
top:0;
left:-100%;
width:50%;
height:100%;
background:linear-gradient(
90deg,
rgba(255,255,255,0) 0%,
rgba(255,255,255,0.8) 50%,
rgba(255,255,255,0) 100%
);
animation:shine 2.5s infinite;
}

/* PLATA */

.bar-silver{
background:linear-gradient(90deg,#d9d9d9,#bfbfbf,#e6e6e6);
}

/* BRONCE */

.bar-bronze{
background:linear-gradient(90deg,#cd7f32,#b87333,#cd7f32);
}

/* RESTO */

.bar-green{
background:linear-gradient(90deg,#00c853,#64dd17);
}

@keyframes shine{
0%{ left:-100%; }
100%{ left:150%; }
}

</style>

<h2 class="mb-4">🏆 Clasificación - Grupo Actual</h2>

<div class="alert alert-info">
Mostrando clasificación de la temporada:
<strong><?php echo htmlspecialchars($temporada['nombre']); ?></strong>
</div>

<?php if(count($ranking) >= 1): ?>

<div class="row text-center mb-4">

<?php
for($i=0;$i<3;$i++):

if(!isset($ranking[$i])) continue;

$medalla = '';
if($i==0) $medalla="🥇";
if($i==1) $medalla="🥈";
if($i==2) $medalla="🥉";
?>

<div class="col-md-4 mb-3">

<div class="card shadow-sm border-warning">

<div class="card-body">

<h3><?php echo $medalla; ?></h3>

<h5><?php echo htmlspecialchars($ranking[$i]['nombre']); ?></h5>

<p class="mb-0">
<strong><?php echo $ranking[$i]['total']; ?> pts</strong>
</p>

</div>
</div>

</div>

<?php endfor; ?>

</div>

<?php endif; ?>


<div class="card shadow-sm">
<div class="card-body">

<table class="table align-middle">

<thead>
<tr>
<th>#</th>
<th>Nombre</th>
<th style="width:45%">Progreso</th>
<th>Puntos</th>
</tr>
</thead>

<tbody>

<?php
$posicion = 1;

foreach($ranking as $row):

$porcentaje = $maxPuntos > 0 ? ($row['total'] / $maxPuntos) * 100 : 0;

$medalla = '';

if ($posicion == 1) $medalla = '🥇';
elseif ($posicion == 2) $medalla = '🥈';
elseif ($posicion == 3) $medalla = '🥉';

$clase = '';

if ($row['id'] == $mi_id) {
$clase = 'table-primary fw-bold';
}
elseif ($posicion == 1) {
$clase = 'table-warning fw-bold';
}

/* COLOR BARRA */

$barClass = "bar-green";

if($posicion==1) $barClass="bar-gold";
elseif($posicion==2) $barClass="bar-silver";
elseif($posicion==3) $barClass="bar-bronze";
?>

<tr class="<?php echo $clase; ?>">

<td>
<?php echo $medalla ? $medalla : $posicion . "º"; ?>
</td>

<td>
<?php echo htmlspecialchars($row["nombre"]); ?>
</td>

<td>

<div class="progress">

<div class="progress-bar <?php echo $barClass; ?>"
style="width: <?php echo $porcentaje; ?>%">

</div>

</div>

</td>

<td>
<strong><?php echo $row["total"]; ?> pts</strong>
</td>

</tr>

<?php
$posicion++;
endforeach;
?>

</tbody>
</table>

</div>
</div>

<?php
$content = ob_get_clean();
require_once "../../layouts/app.php";
?>