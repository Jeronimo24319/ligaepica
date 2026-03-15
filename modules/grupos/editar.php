<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM grupos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$grupo = $resultado->fetch_assoc();

if (!$grupo) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);

    if ($nombre === '') {
        $error = "El nombre es obligatorio.";
    } else {

        $update = $conn->prepare("UPDATE grupos SET nombre = ? WHERE id = ?");
        $update->bind_param("si", $nombre, $id);
        $update->execute();

        header("Location: index.php");
        exit;
    }
}

ob_start();
?>

<h2>Editar Grupo</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

    <div class="mb-3">
        <label class="form-label">Nombre del Grupo</label>
        <input type="text"
               name="nombre"
               class="form-control"
               value="<?php echo htmlspecialchars($grupo['nombre']); ?>"
               required>
    </div>

    <button type="submit" class="btn btn-primary">
        Guardar Cambios
    </button>

    <a href="index.php" class="btn btn-secondary">
        Cancelar
    </a>

</form>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';