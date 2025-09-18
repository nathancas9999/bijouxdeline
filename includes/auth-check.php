<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    die('⛔ Accès refusé. Admin uniquement.');
}
