<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/puntuacion_automatica.php';
procesarPuntuacionesAutomaticas($conn);

$grupo_activo = $_SESSION['grupo_activo'] ?? 0;
$usuario_id = $_SESSION['id'] ?? 0;

if(!$grupo_activo){
die("No hay grupo activo.");
}

$hoy = date('Y-m-d');

/* ===============================
   TEMPORADA ACTIVA
=============================== */

$stmtTemp = $conn->prepare("
SELECT id,nombre
FROM temporadas
WHERE activa=1
LIMIT 1
");

$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();

if($resTemp->num_rows==0){
die("No hay temporada activa.");
}

$temporada = $resTemp->fetch_assoc();
$temporada_id = $temporada['id'];

/* ===============================
   ACTIVIDADES DEL GRUPO
=============================== */

$stmtProx = $conn->prepare("
SELECT id,tipo,fecha,hora,hora_fin,puntos
FROM actividades
WHERE id_grupo=?
AND CONCAT(fecha,' ',hora_fin) >= NOW()
ORDER BY fecha DESC, hora DESC
");

$stmtProx->bind_param("i",$grupo_activo);
$stmtProx->execute();
$resProx = $stmtProx->get_result();

$actividades = [];

/* ===============================
   ACTIVIDADES CERRADAS
=============================== */

$stmtCerradas = $conn->prepare("
SELECT id,tipo,fecha,hora,hora_fin,puntos
FROM actividades
WHERE id_grupo=?
AND CONCAT(fecha,' ',hora_fin) < NOW()
ORDER BY fecha DESC
LIMIT 3
");

$stmtCerradas->bind_param("i",$grupo_activo);
$stmtCerradas->execute();
$resCerradas = $stmtCerradas->get_result();

$actividadesCerradas = [];

while($row = $resCerradas->fetch_assoc()){
$actividadesCerradas[] = $row;
}

while($row = $resProx->fetch_assoc()){
$actividades[] = $row;
}

$ahora = time();

/* ===============================
   MI POSICIÓN
=============================== */

$stmtPos = $conn->prepare("
SELECT posicion,total FROM (

SELECT 
u.id,
u.nombre,
COALESCE(SUM(ap.puntos_ganados),0) AS total,
@pos := @pos + 1 AS posicion
FROM (SELECT @pos := 0) r,
ligaepica_grupos lg
JOIN usuarios u ON u.id=lg.usuario_id
LEFT JOIN actividad_participantes ap 
ON ap.id_usuario=u.id
AND ap.estado='aprobado'
LEFT JOIN actividades a 
ON a.id=ap.id_actividad
AND a.id_temporada=?
WHERE lg.grupo_id=?
GROUP BY u.id
ORDER BY total DESC

) ranking
WHERE id=?
");

$stmtPos->bind_param("iii",$temporada_id,$grupo_activo,$usuario_id);
$stmtPos->execute();
$resPos = $stmtPos->get_result()->fetch_assoc();

$posicion_usuario = $resPos['posicion'] ?? null;
$puntos_usuario = $resPos['total'] ?? 0;

/* ===============================
   MI RACHA
=============================== */

$stmtRacha = $conn->prepare("
SELECT 
a.id,
ap.estado
FROM actividades a
LEFT JOIN actividad_participantes ap
ON ap.id_actividad=a.id
AND ap.id_usuario=?
WHERE a.id_grupo=?
AND a.fecha<=CURDATE()
ORDER BY a.fecha DESC
");

$stmtRacha->bind_param("ii",$usuario_id,$grupo_activo);
$stmtRacha->execute();

$resRacha = $stmtRacha->get_result();

$racha = 0;

while($row = $resRacha->fetch_assoc()){
if($row['estado']=='aprobado'){
$racha++;
}else{
break;
}
}

/* ===============================
   RACHA DEL GRUPO
=============================== */

$stmtRachaGrupo = $conn->prepare("
SELECT 
a.id,
COUNT(ap.id) as participantes
FROM actividades a
LEFT JOIN actividad_participantes ap
ON ap.id_actividad=a.id
AND ap.participo=1
WHERE a.id_grupo=?
AND a.fecha<=CURDATE()
GROUP BY a.id
ORDER BY a.fecha DESC
");

$stmtRachaGrupo->bind_param("i",$grupo_activo);
$stmtRachaGrupo->execute();

$resRachaGrupo = $stmtRachaGrupo->get_result();

$racha_grupo = 0;

while($row = $resRachaGrupo->fetch_assoc()){
if($row['participantes'] > 0){
$racha_grupo++;
}else{
break;
}
}

/* LOGROS */

$logro = '';

if($racha >= 10){
$logro = "🏆 Imparable";
}elseif($racha >= 5){
$logro = "💪 Constante";
}elseif($racha >= 3){
$logro = "🔥 En racha";
}elseif($racha >= 1){
$logro = "🚴 Primera salida";
}

/* ===============================
   INFORMACIÓN DEL GRUPO
=============================== */

$stmtLider = $conn->prepare("
SELECT 
u.nombre,
COALESCE(SUM(ap.puntos_ganados),0) AS total
FROM ligaepica_grupos lg
JOIN usuarios u ON u.id=lg.usuario_id
LEFT JOIN actividad_participantes ap 
ON ap.id_usuario=u.id
AND ap.estado='aprobado'
LEFT JOIN actividades a 
ON a.id=ap.id_actividad
AND a.id_temporada=?
WHERE lg.grupo_id=?
GROUP BY u.id
ORDER BY total DESC
LIMIT 1
");

$stmtLider->bind_param("ii",$temporada_id,$grupo_activo);
$stmtLider->execute();
$lider = $stmtLider->get_result()->fetch_assoc();

$stmtAct = $conn->prepare("
SELECT COUNT(*)
FROM actividades
WHERE id_grupo=? AND id_temporada=?
");

$stmtAct->bind_param("ii",$grupo_activo,$temporada_id);
$stmtAct->execute();
$totalActividades = $stmtAct->get_result()->fetch_row()[0];

$stmtMiembros = $conn->prepare("
SELECT COUNT(*)
FROM ligaepica_grupos
WHERE grupo_id=?
");

$stmtMiembros->bind_param("i",$grupo_activo);
$stmtMiembros->execute();
$totalMiembros = $stmtMiembros->get_result()->fetch_row()[0];

ob_start();
?>

<h2 class="mb-4">Dashboard</h2>

<?php if(!empty($actividades)): ?>

<?php foreach($actividades as $actividad): ?>

<?php

$horaActividad = $actividad['hora'] ?? '00:00:00';
$horaFin = $actividad['hora_fin'] ?? $horaActividad;

$fechaHoraActividad = strtotime($actividad['fecha'].' '.$horaActividad);
$fechaHoraLimite = strtotime("+3 hours", strtotime($actividad['fecha'].' '.$horaFin));

$stmtPart = $conn->prepare("
SELECT COUNT(*)
FROM actividad_participantes
WHERE id_actividad=? AND participo=1
");

$stmtPart->bind_param("i",$actividad['id']);
$stmtPart->execute();
$participantesConfirmados = $stmtPart->get_result()->fetch_row()[0];

$stmtMi = $conn->prepare("
SELECT participo
FROM actividad_participantes
WHERE id_usuario=? AND id_actividad=?
");

$stmtMi->bind_param("ii",$usuario_id,$actividad['id']);
$stmtMi->execute();
$resMi = $stmtMi->get_result()->fetch_assoc();

$miConfirmacion = $resMi['participo'] ?? null;

$actividadEnCurso = ($ahora >= $fechaHoraActividad && $ahora <= $fechaHoraLimite);

?>

<div class="card mb-4 border-primary shadow-sm text-center">
<div class="card-body">

<h5>🚴 Actividad del grupo</h5>

<h3><?= htmlspecialchars($actividad['tipo']) ?></h3>

<p>
📅 <?= date('d/m/Y',strtotime($actividad['fecha'])) ?><br>
🕘 <?= substr($actividad['hora'],0,5) ?>
</p>

<p>⭐ <?= $actividad['puntos'] ?> puntos</p>

<div id="contador<?= $actividad['id'] ?>" class="mt-2 text-muted"></div>

<a href="#"
onclick="toggleConfirmados(<?= $actividad['id'] ?>);return false;"
class="btn btn-light btn-sm">

👥 <?= $participantesConfirmados ?> participantes confirmados

</a>

<div id="confirmadosLista<?= $actividad['id'] ?>" style="display:none;margin-top:10px"></div>

<?php if($miConfirmacion === null && !$actividadEnCurso && $ahora < $fechaHoraActividad): ?>

<a href="../actividades/confirmar_asistencia.php?actividad=<?= $actividad['id'] ?>&estado=si"
class="btn btn-success mt-2">Voy</a>

<a href="../actividades/confirmar_asistencia.php?actividad=<?= $actividad['id'] ?>&estado=no"
class="btn btn-danger mt-2">No voy</a>

<?php endif; ?>

<?php if($miConfirmacion===1): ?>
<div class="badge bg-success mt-2 fs-6">✔ Has confirmado asistencia</div>
<?php endif; ?>

<?php if($miConfirmacion===0): ?>
<div class="badge bg-danger mt-2 fs-6">❌ No asistirás</div>
<?php endif; ?>

</div>
</div>

<?php endforeach; ?>

<h3 class="mt-5 mb-3">🏁 Actividades realizadas</h3>

<?php if(!empty($actividadesCerradas)): ?>

<?php foreach($actividadesCerradas as $actividad): ?>

<div class="card mb-3 shadow-sm text-center">
<div class="card-body">

<h5><?= htmlspecialchars($actividad['tipo']) ?></h5>

<p>
📅 <?= date('d/m/Y',strtotime($actividad['fecha'])) ?><br>
🕘 <?= substr($actividad['hora'],0,5) ?>
</p>

<p>⭐ <?= $actividad['puntos'] ?> puntos</p>

<span class="badge bg-secondary">Actividad finalizada</span>

</div>
</div>

<?php endforeach; ?>

<?php endif; ?>

<div class="row">

<div class="col-md-4 mb-4">
<div class="card text-center shadow-sm">
<div class="card-body">
<h6>🔥 Mi racha</h6>
<h3><?= $racha ?></h3>
<p><?= $racha==1 ? 'actividad seguida' : 'actividades seguidas' ?></p>

<?php if($logro): ?>
<div class="mt-2 badge bg-warning text-dark fs-6">
<?= $logro ?>
</div>
<?php endif; ?>

</div>
</div>
</div>

<div class="col-md-4 mb-4">
<div class="card text-center shadow-sm">
<div class="card-body">
<h6>🔥 Racha del grupo</h6>
<h3><?= $racha_grupo ?></h3>
<p><?= $racha_grupo==1 ? 'actividad seguida' : 'actividades seguidas' ?></p>
</div>
</div>
</div>

<div class="col-md-4 mb-4">
<div class="card text-center shadow-sm">
<div class="card-body">
<h6>🏅 Mi posición</h6>
<h3><?= $posicion_usuario ? $posicion_usuario."º" : "-" ?></h3>
<p><?= $puntos_usuario ?> pts</p>
</div>
</div>
</div>

<div class="col-md-12 mb-4">
<div class="card text-center shadow-sm">
<div class="card-body">

<h6 class="mb-3">📊 Información del grupo</h6>

<p class="mb-1">
👑 <strong>Líder:</strong>
<?= $lider['nombre'] ?? '-' ?> (<?= $lider['total'] ?? 0 ?> pts)
</p>

<p class="mb-1">
👥 <strong><?= $totalMiembros ?></strong> miembros
</p>

<p class="mb-0">
📅 <strong><?= $totalActividades ?></strong> actividades
</p>

</div>
</div>
</div>

</div>

<?php else: ?>

<div class="card mb-4 text-center shadow-sm">
<div class="card-body">
🚴 Aún no hay actividades programadas.
</div>
</div>

<?php endif; ?>


<script>
function toggleConfirmados(actividad){

const lista=document.getElementById("confirmadosLista"+actividad);

if(lista.style.display==="none" || lista.style.display===""){

fetch("/ligaepica_v2/modules/actividades/confirmados.php?actividad="+actividad)

.then(res=>res.text())

.then(data=>{

lista.innerHTML=data;
lista.style.display="block";

});

}else{

lista.style.display="none";

}

}
</script>

<script>

function iniciarContadores(){

const ahora = new Date().getTime();

<?php foreach($actividades as $actividad): ?>

const salida<?= $actividad['id'] ?> = new Date("<?= $actividad['fecha'] ?> <?= $actividad['hora'] ?>").getTime();
const fin<?= $actividad['id'] ?> = new Date("<?= $actividad['fecha'] ?> <?= $actividad['hora_fin'] ?>").getTime();

const contenedor<?= $actividad['id'] ?> = document.getElementById("contador<?= $actividad['id'] ?>");

if(!contenedor<?= $actividad['id'] ?>) return;

if(ahora < salida<?= $actividad['id'] ?>){

const diferencia = salida<?= $actividad['id'] ?> - ahora;

const horas = Math.floor(diferencia / (1000 * 60 * 60));
const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

contenedor<?= $actividad['id'] ?>.innerHTML = "⏳ Salida en " + horas + "h " + minutos + "min";

}

else if(ahora >= salida<?= $actividad['id'] ?> && ahora <= fin<?= $actividad['id'] ?>){

contenedor<?= $actividad['id'] ?>.innerHTML = "🚴 Actividad en curso";

}

else{

contenedor<?= $actividad['id'] ?>.innerHTML = "🏁 Actividad finalizada";

}

<?php endforeach; ?>

}

setInterval(iniciarContadores,1000);
iniciarContadores();

</script>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>