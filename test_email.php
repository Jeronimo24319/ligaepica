<?php

require_once 'includes/email.php';

$enviado = enviarCorreo(
"jeronimo1967bcn@gmail.com",
"Jero",
"Prueba SMTP Liga Epica",
"<h2>Correo funcionando</h2><p>El sistema de correo de Liga Epica está activo.</p>"
);

if($enviado){
    echo "EMAIL ENVIADO";
}else{
    echo "ERROR AL ENVIAR";
}