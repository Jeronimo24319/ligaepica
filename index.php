<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: modules/auth/login.php");
    exit();
}

header("Location: modules/dashboard/index.php");
exit();