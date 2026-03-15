<?php
require_once __DIR__ . '/../config/database.php';

/* Obtener temporada activa */
$stmt = $conn->prepare("SELECT nombre, anio FROM temporadas WHERE activa = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$temporada = $result->fetch_assoc();

$temporada_nombre = $temporada ? $temporada['nombre'] : 'Sin temporada activa';
?>

<nav class="navbar navbar-light bg-white border-bottom px-4 d-flex justify-content-between">

    <div>
        <span class="navbar-brand mb-0 h5">Panel de Control</span>
        <span class="badge bg-dark ms-3">
            <?php echo htmlspecialchars($temporada_nombre); ?>
        </span>
    </div>

    <div>
        <span class="text-muted">
            <?php echo htmlspecialchars($_SESSION['nombre']); ?>
        </span>
    </div>

</nav>