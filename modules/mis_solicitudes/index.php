<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$id_usuario = $_SESSION['id'];

/* Temporada activa */
$stmtTemp = $conn->prepare("SELECT id, nombre FROM temporadas WHERE activa = 1 LIMIT 1");
$stmtTemp->execute();
$resTemp = $stmtTemp->get_result();
$temporada = $resTemp->fetch_assoc();

if (!$temporada) {
    die("No hay temporada activa.");
}

$temporada_id = $temporada['id'];

/* Actividades de la temporada */
$stmtAct = $conn->prepare("
    SELECT a.id, a.fecha, a.tipo, a.puntos
    FROM actividades a
    WHERE a.id_temporada = ?
    ORDER BY a.fecha DESC
");
$stmtAct->bind_param("i", $temporada_id);
$stmtAct->execute();
$actividades = $stmtAct->get_result();

/* Solicitudes del usuario */
$stmtSol = $conn->prepare("
    SELECT ap.*, a.tipo, a.fecha
    FROM actividad_participantes ap
    JOIN actividades a ON ap.id_actividad = a.id
    WHERE ap.id_usuario = ?
    ORDER BY ap.id DESC
");
$stmtSol->bind_param("i", $id_usuario);
$stmtSol->execute();
$solicitudes = $stmtSol->get_result();

ob_start();
?>

<h2 class="mb-4">Mis Solicitudes de Puntos</h2>

<div class="card mb-4">
    <div class="card-body">
        <h5>Solicitar participación</h5>

        <form method="POST" action="solicitar.php">
            <div class="mb-3">
                <label>Actividad</label>
                <select name="id_actividad" class="form-select" required>

                    <?php while ($act = $actividades->fetch_assoc()): ?>
                        <option value="<?php echo $act['id']; ?>">
                            <?php echo $act['fecha'] . " - " . htmlspecialchars($act['tipo']) . " (" . $act['puntos'] . " pts)"; ?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Solicitar puntos
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <h5>Historial</h5>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Actividad</th>
                    <th>Estado</th>
                    <th>Puntos Ganados</th>
                </tr>
            </thead>
            <tbody>

                <?php while ($sol = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $sol['fecha']; ?></td>
                        <td><?php echo htmlspecialchars($sol['tipo']); ?></td>
                        <td>
                            <?php
                                if ($sol['estado'] == 'pendiente') echo '<span class="badge bg-warning">Pendiente</span>';
                                if ($sol['estado'] == 'aprobado') echo '<span class="badge bg-success">Aprobado</span>';
                                if ($sol['estado'] == 'rechazado') echo '<span class="badge bg-danger">Rechazado</span>';
                            ?>
                        </td>
                        <td><?php echo $sol['puntos_ganados']; ?></td>
                    </tr>
                <?php endwhile; ?>

            </tbody>
        </table>

    </div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';