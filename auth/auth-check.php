<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: /login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$userNom = $_SESSION['user']['nom'];
