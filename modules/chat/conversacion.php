<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';
require_once '../../core/notificaciones.php';

if (!isset($_GET['id'])) {
    header("Location: privado.php");
    exit;
}

$conversacion_id = intval($_GET['id']);
$usuario_id = $_SESSION['id'];
$grupo_activo = $_SESSION['grupo_activo'];

/* Validar conversación */
$stmtValidar = $conn->prepare("
    SELECT * FROM conversaciones_privadas
    WHERE id = ?
    AND grupo_id = ?
    AND (usuario1 = ? OR usuario2 = ?)
");

$stmtValidar->bind_param(
    "iiii",
    $conversacion_id,
    $grupo_activo,
    $usuario_id,
    $usuario_id
);

$stmtValidar->execute();
$resValidar = $stmtValidar->get_result();

if ($resValidar->num_rows === 0) {
    die("No tienes permiso para esta conversación.");
}

$conversacion = $resValidar->fetch_assoc();

/* Insertar mensaje */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {

    $mensaje = trim($_POST['mensaje']);

    if (!empty($mensaje)) {

        $stmtInsert = $conn->prepare("
            INSERT INTO mensajes_privados
            (conversacion_id, remitente_id, mensaje)
            VALUES (?, ?, ?)
        ");

        $stmtInsert->bind_param(
            "iis",
            $conversacion_id,
            $usuario_id,
            $mensaje
        );

        $stmtInsert->execute();


        /* ==========================
           CREAR NOTIFICACIÓN
        ========================== */

        $destinatario = ($conversacion['usuario1'] == $usuario_id)
            ? $conversacion['usuario2']
            : $conversacion['usuario1'];

        crearNotificacion(
            $conn,
            $destinatario,
            $usuario_id,
            $grupo_activo,
            null,
            "chat_privado",
            $_SESSION['nombre']." te envió un mensaje"
        );
    }

    exit;
}

ob_start();
?>

<style>
.chat-box {
    height: 60vh;
    overflow-y: auto;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
}

.bubble {
    padding: 10px 15px;
    border-radius: 20px;
    margin-bottom: 10px;
    max-width: 75%;
    word-wrap: break-word;
}

.mine {
    background: #0d6efd;
    color: white;
    align-self: flex-end;
}

.other {
    background: #e4e6eb;
    align-self: flex-start;
}
</style>

<h4 class="mb-3">💬 Conversación Privada</h4>

<div class="chat-box" id="chatBox"></div>

<form id="chatForm" class="mt-3">
<div class="input-group">
<input type="text" name="mensaje" id="mensajeInput" class="form-control" required>
<button class="btn btn-success">Enviar</button>
</div>
</form>

<script>
const chatBox = document.getElementById('chatBox');
const form = document.getElementById('chatForm');
const input = document.getElementById('mensajeInput');
let lastCount = 0;

function cargarMensajes() {

fetch("cargar_mensajes.php?id=<?= $conversacion_id ?>")

.then(res => res.json())

.then(data => {

if (data.length !== lastCount) {

const estabaAbajo =
chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 50;

chatBox.innerHTML = "";

data.forEach(msg => {

const div = document.createElement("div");

div.classList.add("bubble");

if (msg.remitente_id == <?= $usuario_id ?>) {

div.classList.add("mine");

} else {

div.classList.add("other");

}

div.innerHTML =
msg.mensaje +
"<div style='font-size:11px; opacity:0.7; margin-top:5px;'>" +
new Date(msg.fecha).toLocaleString() +
"</div>";

chatBox.appendChild(div);

});

if (estabaAbajo) {

chatBox.scrollTop = chatBox.scrollHeight;

}

lastCount = data.length;

}

});

}

form.addEventListener("submit", function(e){

e.preventDefault();

fetch("", {
method: "POST",
body: new FormData(form)
})

.then(() => {

input.value = "";

cargarMensajes();

});

});

setInterval(cargarMensajes, 3000);

cargarMensajes();

</script>

<?php
$content = ob_get_clean();
require '../../layouts/app.php';