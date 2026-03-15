<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

$grupo_activo = $_SESSION['grupo_activo'];
$usuario_id   = $_SESSION['id'];

/* =========================================
   ACTIVIDADES CON CHAT (GRUPAL)
========================================= */

$stmt = $conn->prepare("
    SELECT a.id, a.tipo, a.fecha,
           COUNT(am.id) as total_mensajes
    FROM actividades a
    LEFT JOIN actividad_mensajes am 
        ON am.actividad_id = a.id
        AND am.grupo_id = ?
    WHERE a.id_grupo = ?
    GROUP BY a.id
    ORDER BY a.fecha DESC
");

$stmt->bind_param("ii", $grupo_activo, $grupo_activo);
$stmt->execute();
$chats_grupales = $stmt->get_result();

/* =========================================
   CONTADOR CONVERSACIONES PRIVADAS
========================================= */

$stmtPrivados = $conn->prepare("
    SELECT COUNT(*) as total
    FROM conversaciones_privadas
    WHERE grupo_id = ?
    AND (usuario1 = ? OR usuario2 = ?)
");

$stmtPrivados->bind_param("iii", $grupo_activo, $usuario_id, $usuario_id);
$stmtPrivados->execute();
$totalPrivados = $stmtPrivados->get_result()->fetch_assoc()['total'] ?? 0;

ob_start();
?>

<h2 class="mb-4">💬 Centro de Chat</h2>

<div class="row">

    <!-- CHAT GRUPAL -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                🏆 Chats de Actividades
            </div>
            <div class="card-body">

                <?php if ($chats_grupales->num_rows > 0): ?>

                    <ul class="list-group list-group-flush">

                        <?php while ($chat = $chats_grupales->fetch_assoc()): ?>

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($chat['tipo']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($chat['fecha'])); ?>
                                    </small>
                                </div>

                                <div class="text-end">
                                    <span class="badge bg-secondary mb-2">
                                        <?php echo $chat['total_mensajes']; ?> mensajes
                                    </span>
                                    <br>
                                    <a href="ver.php?id=<?php echo $chat['id']; ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        Entrar
                                    </a>
                                </div>
                            </li>

                        <?php endwhile; ?>

                    </ul>

                <?php else: ?>

                    <p class="text-muted">No hay actividades con chat aún.</p>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- CHAT PRIVADO -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                👤 Chats Privados
            </div>
            <div class="card-body text-center">

                <p>
                    Conversaciones 1 a 1 dentro del grupo activo.
                </p>

                <?php if ($totalPrivados > 0): ?>
                    <span class="badge bg-success mb-3">
                        <?php echo $totalPrivados; ?> conversaciones activas
                    </span>
                <?php endif; ?>

                <br>

                <a href="privado.php" class="btn btn-success">
                    Ver Conversaciones
                </a>

            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';