<?php
session_start();
include '../includes/db.php';

// Si l'utilisateur n'est pas connecté ou que le panier est vide, on le renvoie au panier.
if (!isset($_SESSION['user']) || empty($_SESSION['panier'])) {
    header('Location: /pages/panier.php');
    exit;
}

// On récupère l'adresse de l'utilisateur directement depuis la base de données pour la sécurité
$stmt = $pdo->prepare("SELECT adresse, code_postal, ville, pays FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$user_address = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'adresse est bien complète, on la sauvegarde en session pour la commande
if ($user_address && !empty($user_address['adresse']) && !empty($user_address['code_postal']) && !empty($user_address['ville'])) {
    $_SESSION['adresse_livraison'] = [
        'adresse' => $user_address['adresse'],
        'code_postal' => $user_address['code_postal'],
        'ville' => $user_address['ville'],
        'pays' => $user_address['pays']
    ];
    // Et on redirige vers la page de paiement
    header('Location: /pages/paiement.php');
    exit;
} else {
    // Si, par hasard, l'adresse n'est pas complète, on l'envoie sur la page checkout pour la saisir
    header('Location: /pages/checkout.php');
    exit;
}