<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/permisos.php';
require_once '../../core/grupos.php';

requiereAdmin();

$grupo_activo = $_SESSION['grupo_activo'];

/* ===============================
COMPROBAR QUE EL GRUPO TIENE MIEMBROS
=============================== */

$stmtMiembros = $conn->prepare("
SELECT COUNT(*)
FROM ligaepica_grupos
WHERE grupo_id = ?
");

$stmtMiembros->bind_param("i",$grupo_activo);
$stmtMiembros->execute();

$total_miembros = $stmtMiembros->get_result()->fetch_row()[0];

if($total_miembros == 0){

ob_start();
?>

<div class="alert alert-danger">
Este grupo no tiene miembros todavía.<br>
No puedes crear actividades hasta que invites usuarios.
</div>

<a href="../grupos/miembros.php?grupo=<?php echo $grupo_activo; ?>"
class="btn btn-primary">
Invitar miembros
</a>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
exit;

}


/* ===============================
OBTENER TEMPORADA ACTIVA
=============================== */

$stmtTemp = $conn->prepare("
SELECT id
FROM temporadas
WHERE activa = 1
LIMIT 1
");

$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();
$temporada = $resTemp->fetch_assoc();

if (!$temporada) {
    die("No hay temporada activa.");
}

$temporada_id = $temporada['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$tipo = trim($_POST['tipo']);
$fecha = $_POST['fecha'];
$hora = $_POST['hora'] ?? null;
$hora_fin = $_POST['hora_fin'] ?? null;
$puntos = intval($_POST['puntos']);

if($hora === '') $hora = null;
if($hora_fin === '') $hora_fin = null;

/* =====================================
   EVITAR ACTIVIDADES DUPLICADAS
===================================== */

$stmtCheck = $conn->prepare("
SELECT id
FROM actividades
WHERE fecha = ?
AND tipo = ?
AND id_grupo = ?
AND id_temporada = ?
AND (hora = ? OR (? IS NULL AND hora IS NULL))
LIMIT 1
");

$stmtCheck->bind_param(
"ssiiss",
$fecha,
$tipo,
$grupo_activo,
$temporada_id,
$hora,
$hora
);

$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if($resCheck->num_rows > 0){

header("Location: index.php");
exit;

}


/* =====================================
   CREAR ACTIVIDAD
===================================== */

$stmt = $conn->prepare("
INSERT INTO actividades
(tipo, fecha, hora, hora_fin, puntos, id_grupo, id_temporada)
VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
"ssssiii",
$tipo,
$fecha,
$hora,
$hora_fin,
$puntos,
$grupo_activo,
$temporada_id
);

$stmt->execute();

if($stmt->affected_rows <= 0){
die("Error al crear la actividad.");
}

/* actualizar actividad del grupo */
actualizarActividadGrupo($conn, $grupo_activo);

/* ID DE LA ACTIVIDAD */

$actividad_id = $stmt->insert_id;

if(!$actividad_id){
die("Error obteniendo el ID de la actividad.");
}


/* =====================================
   INSCRIBIR AUTOMÁTICAMENTE USUARIOS
===================================== */

$stmtInsert = $conn->prepare("
INSERT IGNORE INTO actividad_participantes
(id_actividad, id_usuario, participo, puntos_ganados, estado)

SELECT
?,
usuario_id,
NULL,
0,
'pendiente'

FROM ligaepica_grupos
WHERE grupo_id = ?
");

$stmtInsert->bind_param(
"ii",
$actividad_id,
$grupo_activo
);

$stmtInsert->execute();


/* =====================================
   CREAR NOTIFICACIONES
===================================== */

$horaTexto = $hora ? " a las ".substr($hora,0,5) : "";
$mensaje = "Nueva actividad creada: $tipo ($fecha$horaTexto)";

$stmtNotif = $conn->prepare("
INSERT INTO notificaciones (usuario_id, mensaje, leido, fecha)

SELECT
usuario_id,
?,
0,
NOW()

FROM ligaepica_grupos
WHERE grupo_id = ?
");

$stmtNotif->bind_param(
"si",
$mensaje,
$grupo_activo
);

$stmtNotif->execute();


header("Location: index.php");
exit;

}

ob_start();
?>

<h2 class="mb-4">Crear Actividad</h2>

<div class="card">
<div class="card-body">

<form method="POST">

<div class="mb-3">
<label class="form-label">Tipo</label>
<input type="text" name="tipo" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Fecha</label>
<input type="date" name="fecha" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Hora de salida</label>
<input type="time" name="hora" class="form-control">
</div>

<div class="mb-3">
<label class="form-label">Hora fin</label>
<input type="time" name="hora_fin" class="form-control">
</div>

<div class="mb-3">
<label class="form-label">Puntos</label>
<input type="number" name="puntos" class="form-control" required>
</div>

<button class="btn btn-primary"
onclick="this.disabled=true; this.innerText='Guardando...'; this.form.submit();">
Guardar Actividad
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
?>