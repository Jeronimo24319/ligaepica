<footer class="text-center py-3 border-top small text-muted">
Liga Épica © <?php echo date('Y'); ?>
</footer>

<script>

function cargarNotificaciones(){

fetch("/ligaepica_v2/ajax/notificaciones.php")

.then(res => res.json())

.then(data => {

console.log("Notificaciones:", data);

});

}

setInterval(cargarNotificaciones, 5000);
/* prueba inmediata */
cargarNotificaciones();
</script>