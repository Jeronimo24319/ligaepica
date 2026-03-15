<?php
if (!isset($content)) $content = '';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/permisos.php';
require_once __DIR__ . '/../core/puntuacion_automatica.php';
procesarPuntuacionesAutomaticas($conn);

/* VERIFICAR TEMPORADA AUTOMÁTICAMENTE */
require_once __DIR__ . '/../includes/verificar_temporada.php';
require_once __DIR__ . '/../includes/verificar_grupos.php';

$currentModule = basename(dirname($_SERVER['PHP_SELF']));
$currentFile   = basename($_SERVER['PHP_SELF']);

$grupo_activo = $_SESSION['grupo_activo'] ?? 0;
$usuario_id   = $_SESSION['id'] ?? 0;

/* =========================
OBTENER NOMBRE DEL GRUPO
========================= */

$nombre_grupo = "";

if($grupo_activo){

$stmtGrupo = $conn->prepare("
SELECT nombre
FROM grupos
WHERE id = ?
LIMIT 1
");

$stmtGrupo->bind_param("i",$grupo_activo);
$stmtGrupo->execute();

$resGrupo = $stmtGrupo->get_result();

if($g = $resGrupo->fetch_assoc()){
$nombre_grupo = $g['nombre'];
}

}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Liga Épica</title>

<link rel="manifest" href="/ligaepica_v2/manifest.json">
<meta name="theme-color" content="#ff6a00">

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* ===== COLOR DE MARCA ===== */

:root{
--epica:#ff6a00;
--epica-dark:#e65f00;
--epica-light:#fff3e8;
}

body{
background:#f4f6f9;
margin:0;
font-size:15px;
color:#333;
}

/* ===== SIDEBAR ===== */

.sidebar{
width:250px;
height:100vh;
background:linear-gradient(180deg,#1e2228,#121417);
overflow-y:auto;
overflow-x:hidden;
transition:transform 0.3s ease;
}

.sidebar img{
width:90px;
display:block;
margin:12px auto;
}

/* CABECERA SIDEBAR */

.sidebar-header{
text-align:center;
padding-bottom:10px;
border-bottom:1px solid rgba(255,255,255,0.08);
margin-bottom:15px;
}

.sidebar-header small{
color:#aaa;
font-size:12px;
}

.sidebar .nav-link{
color:#ccc;
border-radius:10px;
margin-bottom:6px;
padding:10px 12px;
display:flex;
align-items:center;
gap:8px;
font-size:15px;
}

.sidebar .nav-link:hover{
background:#2c3138;
color:#fff;
}

.sidebar .nav-link.active{
background:var(--epica);
color:#fff;
}

/* ===== CONTENIDO ===== */

.main-content{
padding:24px;
width:100%;
max-width:1100px;
margin:auto;
}

/* ===== CARDS ===== */

.card{
border-radius:14px;
border:none;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
margin-bottom:18px;
background:white;
}

.card-body{
padding:18px;
}

/* ===== TITULOS ===== */

h2{
font-weight:600;
font-size:22px;
margin-bottom:20px;
}

/* ===== ALERTAS ===== */

.alert{
border-radius:10px;
padding:12px 16px;
font-size:14px;
}

.alert-secondary{
background:var(--epica-light);
border:1px solid #ffd7b8;
}

/* ===== TABLAS ===== */

.table{
font-size:14px;
}

.table thead{
background:#f1f3f5;
}

.table td{
vertical-align:middle;
padding:12px;
}

/* ===== BADGES ===== */

.badge{
border-radius:8px;
font-size:12px;
padding:6px 10px;
}

/* ===== BOTONES ===== */

.btn{
border-radius:8px;
padding:6px 12px;
font-size:14px;
}

.btn-primary{
background:var(--epica);
border-color:var(--epica);
}

.btn-primary:hover{
background:var(--epica-dark);
border-color:var(--epica-dark);
}

/* BOTÓN RECHAZAR MEJORADO */

.btn-rechazar{
background:#e9ecef;
border:1px solid #d1d5db;
color:#444;
}

.btn-rechazar:hover{
background:#dfe3e7;
border-color:#c8ccd1;
color:#222;
}

td .btn{
margin-right:6px;
margin-bottom:4px;
}

/* ===== OVERLAY ===== */

.overlay{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.4);
z-index:998;
}

/* ===== PANEL NOTIFICACIONES ===== */

.panelNotificaciones{
position:absolute;
right:20px;
top:60px;
width:320px;
background:white;
border-radius:12px;
box-shadow:0 10px 25px rgba(0,0,0,0.25);
display:none;
max-height:400px;
overflow-y:auto;
z-index:9999;
}

.panelNotificaciones a{
display:block;
padding:12px;
border-bottom:1px solid #eee;
text-decoration:none;
color:#333;
}

.panelNotificaciones a:hover{
background:#f5f5f5;
}

/* ===== MODO MOVIL ===== */

@media (max-width:768px){

.sidebar{
width:240px;
position:fixed;
top:0;
left:0;
transform:translateX(-100%);
z-index:999;
}

.sidebar.active{
transform:translateX(0);
}

.overlay.active{
display:block;
}

.sidebar img{
width:100%;
max-width:200px;
display:block;
margin:0 auto 12px auto;
}

.main-content{
padding:18px;
}

.card-body{
padding:16px;
}

.alert{
font-size:14px;
}

.table{
font-size:13px;
}

}

</style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark d-md-none">
<div class="container-fluid">

<button class="btn btn-outline-light" onclick="openSidebar()">☰</button>

<span class="navbar-brand">Liga Épica</span>

<div style="position:relative;cursor:pointer" onclick="toggleNotificaciones()">

🔔

<span id="badgeNotificaciones"
class="badge bg-danger"
style="position:absolute;top:-5px;right:-10px;display:none">
0
</span>

</div>

</div>
</nav>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="d-flex" style="min-height:100vh">

<!-- SIDEBAR -->

<div id="sidebar" class="sidebar text-white p-3 d-flex flex-column">

<div class="sidebar-header text-center mb-4">

<img src="/ligaepica_v2/assets/img/app-icon.png" 
     alt="Liga Épica" 
  style="width:300px;height:auto;object-fit:contain;margin-bottom:12px;">

<div style="color:#fff;font-weight:600;font-size:16px;">
Liga Épica
</div>

<div style="font-size:11px;color:#888;">
ligaepicaciclista.com
</div>

</div>

<ul class="nav nav-pills flex-column mb-auto">

<li>
<a href="/ligaepica_v2/modules/dashboard/index.php"
class="nav-link <?= $currentModule=='dashboard'?'active':'' ?>">
📊 Dashboard
</a>
</li>

<li>
<a href="/ligaepica_v2/modules/perfil/index.php"
class="nav-link <?= $currentModule=='perfil'?'active':'' ?>">
👤 Mi Perfil
</a>
</li>

<li>
<a href="/ligaepica_v2/modules/actividades/index.php"
class="nav-link <?= ($currentModule=='actividades' && $currentFile=='index.php')?'active':'' ?>">
🏆 Actividades
</a>
</li>

<li>
<a href="/ligaepica_v2/modules/actividades/mis_participaciones.php"
class="nav-link <?= ($currentModule=='actividades' && $currentFile=='mis_participaciones.php')?'active':'' ?>">
📝 Mis Participaciones
</a>
</li>

<li>
<a href="/ligaepica_v2/modules/clasificacion/index.php"
class="nav-link <?= $currentModule=='clasificacion'?'active':'' ?>">
🏅 Clasificación
</a>
</li>

<li>
<a href="#" class="nav-link" onclick="abrirTutorial(); return false;">
❓ Tutorial
</a>
</li>

<?php if(esAdmin()): ?>

<li>
<a href="/ligaepica_v2/modules/usuarios/index.php"
class="nav-link <?= $currentModule=='usuarios'?'active':'' ?>">
👥 Usuarios
</a>
</li>

<li>
<a href="/ligaepica_v2/modules/grupos/index.php"
class="nav-link <?= $currentModule=='grupos'?'active':'' ?>">
👑 Grupos
</a>
</li>

<?php endif; ?>

</ul>

<hr>

<a href="/ligaepica_v2/modules/auth/seleccionar_grupo.php" class="nav-link text-warning">
🔄 Cambiar grupo
</a>

<a href="/ligaepica_v2/modules/auth/logout.php" class="nav-link text-danger">
🚪 Cerrar sesión
</a>

</div>

<!-- CONTENIDO -->

<div class="main-content">

<div id="panelNotificaciones" class="panelNotificaciones"></div>

<?php if($nombre_grupo): ?>

<div class="alert alert-secondary mb-3">
🏁 Grupo actual: <strong><?= htmlspecialchars($nombre_grupo) ?></strong>
</div>

<?php endif; ?>

<div class="table-responsive">
<?php echo $content; ?>
</div>

</div>

</div>

<script>

const sidebar=document.getElementById('sidebar');
const overlay=document.getElementById('overlay');

function openSidebar(){
sidebar.classList.add('active');
overlay.classList.add('active');
}

function closeSidebar(){
sidebar.classList.remove('active');
overlay.classList.remove('active');
}

let startX=0;

document.addEventListener('touchstart',e=>{
startX=e.changedTouches[0].screenX;
});

document.addEventListener('touchend',e=>{

let endX=e.changedTouches[0].screenX;

if(endX-startX>70) openSidebar();
if(startX-endX>70) closeSidebar();

});

function toggleNotificaciones(){

const panel=document.getElementById("panelNotificaciones");

if(panel.style.display==="none" || panel.style.display===""){

panel.style.display="block";
cargarNotificaciones();

}else{

panel.style.display="none";

}

}

function cargarNotificaciones(){

fetch('/ligaepica_v2/modules/notificaciones/lista.php')

.then(res=>res.json())

.then(data=>{

const panel=document.getElementById("panelNotificaciones");

panel.innerHTML="";

if(data.length===0){

panel.innerHTML="<div style='padding:10px'>No hay notificaciones</div>";
return;

}

data.forEach(n=>{

const link=document.createElement("a");

link.href=n.url;

link.innerHTML=
"<strong>"+(n.nombre ?? "Sistema")+"</strong><br>"+
"<small>"+n.mensaje+"</small>";

panel.appendChild(link);

});

});

}

function actualizarNotificaciones(){

fetch('/ligaepica_v2/modules/notificaciones/contador.php')

.then(res=>res.json())

.then(data=>{

const badge=document.getElementById('badgeNotificaciones');

if(!badge) return;

if(data.total>0){

badge.innerText=data.total;
badge.style.display='inline-block';

}else{

badge.style.display='none';

}

});

}

setInterval(actualizarNotificaciones,5000);
actualizarNotificaciones();

</script>

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/ligaepica_v2/service-worker.js')
      .then(function(registration) {
        console.log('Service Worker registrado correctamente');
      })
      .catch(function(error) {
        console.log('Error al registrar Service Worker:', error);
      });
  });
}
</script>

<script>

function abrirTutorial(){

alert(
"📘 Cómo funciona Liga Épica\n\n"+
"1️⃣ Crea o únete a un grupo\n"+
"2️⃣ Participa en actividades\n"+
"3️⃣ Marca 'Voy' si participas\n"+
"4️⃣ Después pulsa ⭐ Puntúame\n"+
"5️⃣ Sube en el ranking del grupo\n"+
"6️⃣ Mantén tu racha activa"
);

}

</script>

</body>
</html>
