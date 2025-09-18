<?php
session_start();
unset($_SESSION['promo']);
unset($_SESSION['message_promo']); 
header('Location: ../pages/panier.php');
exit;