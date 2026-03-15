<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

function enviarCorreo($destino, $nombre, $asunto, $mensaje){

$mail = new PHPMailer(true);

try {

$mail->isSMTP();
$mail->Host = 'smtp.hostinger.com';
$mail->SMTPAuth = true;
$mail->Username = 'no-reply@ligaepicaciclista.com';
$mail->Password = 'Pirineos94002*';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;

$mail->setFrom('no-reply@ligaepicaciclista.com', 'Liga Épica');

$mail->addAddress($destino, $nombre);

$mail->isHTML(true);

$mail->Subject = $asunto;
$mail->Body    = $mensaje;

$mail->send();

return true;

} catch (Exception $e) {

echo "Error: " . $mail->ErrorInfo;

return false;

}

}