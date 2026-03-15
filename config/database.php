<?php

$host = "localhost";
$user = "u294310791_ligauser";
$password = "Pirineos94002*";
$dbname = "u294310791_ligaepica";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos.");
}

$conn->set_charset("utf8mb4");