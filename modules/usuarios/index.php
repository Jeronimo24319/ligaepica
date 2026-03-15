<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$rol = $_SESSION['rol'] ?? '';
$rol_grupo = $_SESSION['rol_grupo'] ?? '';
$grupo_activo = $_SESSION['grupo_activo'] ?? 0;

/* Permisos */

if ($rol !== 'admin' && $rol !== 'superadmin' && $rol_grupo !== 'admin') {
    die("Acceso restringido.");
}

/* ==============================
   TEMPORADA ACTIVA
================================ */

$temp = $conn->query("SELECT id FROM temporadas WHERE activa = 1 LIMIT 1");
$temporada = $temp->fetch_assoc();
$temporada_id = $temporada['id'];

/* ==============================
   SUPERADMIN
================================ */

if ($rol === 'admin' || $rol === 'superadmin') {

$result = $conn->query("
SELECT 
u.id,
u.nombre,
u.email,
u.rol,

COALESCE(SUM(ap.puntos_ganados),0) AS puntos,

COUNT(CASE WHEN ap.estado='aprobado' THEN 1 END) AS participaciones

FROM usuarios u

LEFT JOIN actividad_participantes ap
ON ap.id_usuario=u.id
AND ap.estado='aprobado'

LEFT JOIN actividades a
ON a.id=ap.id_actividad
AND a.id_temporada=$temporada_id

GROUP BY u.id
ORDER BY u.nombre
");

}

/* ==============================
   ADMIN DE GRUPO
================================ */

else {

$stmt = $conn->prepare("
SELECT 
u.id,
u.nombre,
u.email,
lg.rol_grupo,

COALESCE(SUM(ap.puntos_ganados),0) AS puntos,

COUNT(CASE WHEN ap.estado='aprobado' THEN 1 END) AS participaciones

FROM ligaepica_grupos lg

JOIN usuarios u ON u.id = lg.usuario_id

LEFT JOIN actividad_participantes ap
ON ap.id_usuario=u.id
AND ap.estado='aprobado'

LEFT JOIN actividades a
ON a.id=ap.id_actividad
AND a.id_temporada=?

WHERE lg.grupo_id=?

GROUP BY u.id
ORDER BY u.nombre
");

$stmt->bind_param("ii",$temporada_id,$grupo_activo);
$stmt->execute();
$result = $stmt->get_result();

}

/* ==============================
   GENERAR ARRAY
================================ */

$usuarios=[];

while($row=$result->fetch_assoc()){
$usuarios[]=$row;
}

/* ==============================
   CALCULAR RANKING
================================ */

usort($usuarios,function($a,$b){
return $b['puntos'] <=> $a['puntos'];
});

$pos=1;

foreach($usuarios as &$u){
$u['posicion']=$pos++;
}

ob_start();
?>

<style>

.usuario-card{
border-radius:16px;
border:none;
box-shadow:0 3px 10px rgba(0,0,0,0.1);
transition:all .2s ease;
}

.usuario-card:hover{
transform:translateY(-3px);
box-shadow:0 8px 20px rgba(0,0,0,0.18);
}

.avatar{
width:55px;
height:55px;
border-radius:50%;
display:flex;
align-items:center;
justify-content:center;
font-weight:bold;
color:white;
font-size:22px;
background:linear-gradient(135deg,#ff6a00,#ff9800);
}

.usuario-email{
font-size:14px;
color:#777;
}

.usuario-stats{
font-size:14px;
margin-top:6px;
}

.usuario-stats span{
margin-right:10px;
}

</style>

<h2 class="mb-4">👥 Gestión de Usuarios</h2>

<div class="row">

<?php foreach($usuarios as $usuario):

$inicial=strtoupper(substr($usuario['nombre'],0,1));

$badge="<span class='badge bg-secondary'>Usuario</span>";

if(($usuario['rol'] ?? '')==='admin'){
$badge="<span class='badge bg-danger'>Admin</span>";
}
elseif(($usuario['rol_grupo'] ?? '')==='admin'){
$badge="<span class='badge bg-warning text-dark'>Admin Grupo</span>";
}

?>

<div class="col-md-6 col-lg-4 mb-3">

<div class="card usuario-card h-100">

<div class="card-body d-flex">

<div class="avatar me-3">
<?php echo $inicial; ?>
</div>

<div class="flex-grow-1">

<h6 class="mb-1">
<?php echo htmlspecialchars($usuario['nombre']); ?>
</h6>

<div class="usuario-email">
<?php echo htmlspecialchars($usuario['email']); ?>
</div>

<div class="usuario-stats">

<span>⭐ <?php echo $usuario['puntos']; ?> pts</span>

<span>🏅 <?php echo $usuario['posicion']; ?>º</span>

<span>🚴 <?php echo $usuario['participaciones']; ?></span>

</div>

<div class="mt-2">
<?php echo $badge; ?>
</div>

</div>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>