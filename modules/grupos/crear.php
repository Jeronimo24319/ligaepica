<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

/* usuario actual */
$usuario_id = $_SESSION['id'];

/* Obtener temporada activa */
$stmtTemp = $conn->prepare("SELECT id FROM temporadas WHERE activa = 1 LIMIT 1");
$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();
$temporada = $resTemp->fetch_assoc();

if (!$temporada) {
    die("No hay temporada activa.");
}

$temporada_id = $temporada['id'];

$error = '';

/* =========================
   CONTAR GRUPOS CREADOS
========================= */

$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM grupos
WHERE creador_id = ?
");

$stmt->bind_param("i",$usuario_id);
$stmt->execute();
$total_grupos = $stmt->get_result()->fetch_assoc()['total'];

if($total_grupos >= 5){
    die("Has alcanzado el máximo de 5 grupos creados.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);
    $tipo = trim($_POST['tipo']);
    $nivel = trim($_POST['nivel']);
    $ubicacion = trim($_POST['ubicacion']);
    $descripcion = trim($_POST['descripcion']);

    /* TODOS LOS GRUPOS SERÁN SOLO INVITACIÓN */
    $tipo_acceso = 'invitacion';

    if ($nombre === '') {

        $error = "El nombre del grupo es obligatorio.";

    } else {

        /* =========================
           EVITAR DUPLICAR TIPO
        ========================= */

        $stmt = $conn->prepare("
        SELECT id
        FROM grupos
        WHERE creador_id = ?
        AND tipo = ?
        ");

        $stmt->bind_param("is",$usuario_id,$tipo);
        $stmt->execute();

        if($stmt->get_result()->num_rows > 0){

            $error = "Ya tienes un grupo de tipo $tipo.";

        } else {

            /* =========================
               CREAR GRUPO
            ========================= */

            $stmt = $conn->prepare("
            INSERT INTO grupos 
            (nombre, tipo, nivel, ubicacion, descripcion, temporada_id, creador_id, tipo_acceso, estado, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo', 1)
            ");

            $stmt->bind_param(
                "sssssiss",
                $nombre,
                $tipo,
                $nivel,
                $ubicacion,
                $descripcion,
                $temporada_id,
                $usuario_id,
                $tipo_acceso
            );

            $stmt->execute();

            /* ID DEL GRUPO CREADO */
            $grupo_id = $conn->insert_id;

            /* AÑADIR CREADOR COMO ADMIN DEL GRUPO */

            $stmtMiembro = $conn->prepare("
            INSERT INTO ligaepica_grupos (grupo_id, usuario_id, rol_grupo)
            VALUES (?, ?, 'admin')
            ");

            $stmtMiembro->bind_param("ii",$grupo_id,$usuario_id);
            $stmtMiembro->execute();

            /* ACTIVAR GRUPO AUTOMÁTICAMENTE */

            $_SESSION['grupo_activo'] = $grupo_id;

            /* IR DIRECTO AL DASHBOARD */

            header("Location: ../dashboard/index.php");
            exit;
        }
    }
}

ob_start();
?>

<h2>Crear Grupo</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
<label class="form-label">Nombre del Grupo</label>
<input type="text" name="nombre" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Tipo de actividad</label>
<select name="tipo" class="form-control" required>

<option value="">Seleccionar</option>
<option value="BTT">BTT</option>
<option value="Carretera">Carretera</option>
<option value="Senderismo">Senderismo</option>
<option value="Running">Running</option>
<option value="Gravel">Gravel</option>

</select>
</div>

<div class="mb-3">
<label class="form-label">Nivel</label>

<select name="nivel" class="form-control">

<option value="Suave">Suave</option>
<option value="Medio">Medio</option>
<option value="Alto">Alto</option>

</select>

</div>

<div class="mb-3">
<label class="form-label">Ubicación</label>
<input type="text" name="ubicacion" class="form-control">
</div>

<div class="mb-3">
<label class="form-label">Descripción</label>
<textarea name="descripcion" class="form-control"></textarea>
</div>

<button type="submit" class="btn btn-primary">
Guardar Grupo
</button>

<a href="index.php" class="btn btn-secondary">
Cancelar
</a>

</form>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>