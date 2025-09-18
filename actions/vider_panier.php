<?php
session_start();

// On supprime simplement la variable 'panier' de la session
unset($_SESSION['panier']);

// On redirige l'utilisateur vers la page du panier (qui sera maintenant vide)
header('Location: ../pages/panier.php');
exit;