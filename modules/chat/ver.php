<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$actividad_id = intval($_GET['id']);
$grupo_activo = $_SESSION['grupo_activo'];
$usuario_id = $_SESSION['id'];

/* VALIDAR ACTIVIDAD */
$stmtActividad = $conn->prepare("
    SELECT id, tipo, fecha, id_grupo
    FROM actividades
    WHERE id = ?
    LIMIT 1
");
$stmtActividad->bind_param("i", $actividad_id);
$stmtActividad->execute();
$resActividad = $stmtActividad->get_result();

if ($resActividad->num_rows === 0) {
    die("Actividad no encontrada.");
}

$actividad = $resActividad->fetch_assoc();

if ($actividad['id_grupo'] != $grupo_activo) {
    die("No tienes permiso para ver esta actividad.");
}

/* MARCAR COMO LEÍDOS */
$stmtMarcar = $conn->prepare("
    INSERT IGNORE INTO actividad_mensajes_leidos (mensaje_id, usuario_id)
    SELECT am.id, ?
    FROM actividad_mensajes am
    LEFT JOIN actividad_mensajes_leidos aml 
        ON aml.mensaje_id = am.id 
        AND aml.usuario_id = ?
    WHERE am.actividad_id = ?
    AND am.grupo_id = ?
    AND am.id_usuario != ?
    AND aml.id IS NULL
");
$stmtMarcar->bind_param("iiiii", $usuario_id, $usuario_id, $actividad_id, $grupo_activo, $usuario_id);
$stmtMarcar->execute();

/* INSERTAR MENSAJE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {

    $mensaje = trim($_POST['mensaje']);

    if (!empty($mensaje)) {
        $stmtInsert = $conn->prepare("
            INSERT INTO actividad_mensajes
            (actividad_id, grupo_id, id_usuario, mensaje)
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->bind_param("iiis", $actividad_id, $grupo_activo, $usuario_id, $mensaje);
        $stmtInsert->execute();
    }

    header("Location: ver.php?id=" . $actividad_id);
    exit;
}

/* OBTENER MENSAJES */
$stmtChat = $conn->prepare("
    SELECT am.id, am.mensaje, am.fecha, u.nombre, am.id_usuario
    FROM actividad_mensajes am
    INNER JOIN usuarios u ON am.id_usuario = u.id
    WHERE am.actividad_id = ?
    AND am.grupo_id = ?
    ORDER BY am.fecha ASC
");
$stmtChat->bind_param("ii", $actividad_id, $grupo_activo);
$stmtChat->execute();
$mensajes = $stmtChat->get_result();

ob_start();
?>

<style>
.chat-container {
    height: 60vh;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.chat-bubble {
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 10px;
    max-width: 75%;
    word-wrap: break-word;
}

.chat-left {
    background: #e4e6eb;
    align-self: flex-start;
}

.chat-right {
    background: #0d6efd;
    color: white;
    align-self: flex-end;
}

.chat-wrapper {
    display: flex;
    flex-direction: column;
}
</style>

<h4 class="mb-3">💬 <?php echo htmlspecialchars($actividad['tipo']); ?></h4>
<p class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($actividad['fecha'])); ?></p>

<div class="card mb-3">
    <div class="card-body chat-container" id="chatBox">

        <div class="chat-wrapper">

        <?php if ($mensajes->num_rows > 0): ?>

            <?php while ($msg = $mensajes->fetch_assoc()): ?>

                <?php
                $propio = ($msg['id_usuario'] == $usuario_id);
                ?>

                <div class="chat-bubble <?php echo $propio ? 'chat-right' : 'chat-left'; ?>">
                    <?php if (!$propio): ?>
                        <strong><?php echo htmlspecialchars($msg['nombre']); ?></strong><br>
                    <?php endif; ?>

                    <?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?>

                    <div style="font-size:11px; opacity:0.7; margin-top:5px;">
                        <?php echo date('d/m H:i', strtotime($msg['fecha'])); ?>
                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="text-muted">No hay mensajes aún.</p>

        <?php endif; ?>

        </div>

    </div>
</div>

<form method="POST">
    <div class="input-group">
        <button type="button" class="btn btn-outline-secondary" onclick="addEmoji('😊')">😊</button>
        <button type="button" class="btn btn-outline-secondary" onclick="addEmoji('😂')">😂</button>
        <button type="button" class="btn btn-outline-secondary" onclick="addEmoji('🔥')">🔥</button>
        <button type="button" class="btn btn-outline-secondary" onclick="addEmoji('🥇')">🥇</button>

        <input type="text"
               id="mensajeInput"
               name="mensaje"
               class="form-control"
               placeholder="Escribe un mensaje..."
               required>

        <button type="submit" class="btn btn-primary">
            Enviar
        </button>
    </div>
</form>

<script>
const chatBox = document.getElementById('chatBox');
chatBox.scrollTop = chatBox.scrollHeight;

function addEmoji(emoji) {
    const input = document.getElementById('mensajeInput');
    input.value += emoji;
    input.focus();
}
</script>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';