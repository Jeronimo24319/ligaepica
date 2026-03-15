<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$usuario_id = $_SESSION['id'] ?? 0;

if(!$usuario_id){
    die("Usuario no válido.");
}

/* Obtener grupos del usuario */
$stmt = $conn->prepare("
    SELECT g.id, g.nombre
    FROM ligaepica_grupos lg
    JOIN grupos g ON lg.grupo_id = g.id
    WHERE lg.usuario_id = ?
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$grupos = [];

while ($row = $resultado->fetch_assoc()) {
    $grupos[] = $row;
}

/* Si no pertenece a ningún grupo → ir a crear grupo */
if(count($grupos) === 0){

    header("Location: ../grupos/crear.php");
    exit;

}

/* Si solo pertenece a 1 grupo → entrar automáticamente */
if (count($grupos) === 1) {

    $_SESSION['grupo_activo'] = $grupos[0]['id'];

    header("Location: ../dashboard/index.php");
    exit;
}

ob_start();
?>

<h2>Selecciona el grupo al que quieres entrar</h2>

<div class="card mt-4">
<div class="card-body">

<?php foreach ($grupos as $grupo): ?>

<form method="POST" action="establecer_grupo.php" class="mb-3">

<input type="hidden" name="grupo_id" value="<?= $grupo['id'] ?>">

<button class="btn btn-primary w-100">
<?= htmlspecialchars($grupo['nombre']) ?>
</button>

</form>

<?php endforeach; ?>

</div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';
?>