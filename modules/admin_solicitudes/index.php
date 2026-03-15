<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

/* Solo admin puede entrar */
if ($_SESSION['rol'] !== 'admin') {
    die("Acceso restringido.");
}

/* Solicitudes pendientes */
$sql = "
    SELECT ap.*, u.nombre as usuario_nombre,
           a.tipo, a.puntos
    FROM actividad_participantes ap
    JOIN usuarios u ON ap.id_usuario = u.id
    JOIN actividades a ON ap.id_actividad = a.id
    WHERE ap.estado = 'pendiente'
    ORDER BY ap.id DESC
";

$resultado = $conn->query($sql);

ob_start();
?>

<h2 class="mb-4">Solicitudes Pendientes</h2>

<div class="card">
    <div class="card-body">

        <?php if ($resultado->num_rows > 0): ?>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Actividad</th>
                        <th>Puntos Base</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>

                    <?php while ($fila = $resultado->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($fila['usuario_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($fila['tipo']); ?></td>
                            <td><?php echo $fila['puntos']; ?></td>
                            <td>
                                <a href="aprobar.php?id=<?php echo $fila['id']; ?>" class="btn btn-success btn-sm">✔ Aprobar</a>
                                <a href="rechazar.php?id=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm">✖ Rechazar</a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

        <?php else: ?>

            <p class="text-muted">No hay solicitudes pendientes.</p>

        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';