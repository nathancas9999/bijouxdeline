<?php
session_start();
require_once('../includes/db.php');

$id_produit = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id_produit > 0 && isset($_SESSION['panier'][$id_produit])) {
    $produit_db = getProduitById($pdo, $id_produit); // On récupère les infos du produit
    if (!$produit_db) {
        // Gérer le cas où le produit n'est pas trouvé (ça ne devrait pas arriver ici)
        header('Location: ../pages/panier.php');
        exit;
    }
    $nom_produit = $produit_db['nom']; // On prend le nom du produit

    switch ($action) {
      case 'plus':
        $stock_disponible = $produit_db['stock']; // On utilise le stock du produit récupéré
        $quantite_panier = $_SESSION['panier'][$id_produit]['qte'];

        if ($quantite_panier < $stock_disponible) {
            $_SESSION['panier'][$id_produit]['qte']++;
        } else {
            $_SESSION['message_panier'] = [
                'type' => 'error',
                // Message amélioré avec le nom du produit
                'text' => '⚠️Attention "' . htmlspecialchars($nom_produit) . '" : stock maximum atteint.' 
            ];
        }
        break;

      case 'moins':
        if ($_SESSION['panier'][$id_produit]['qte'] > 1) {
            $_SESSION['panier'][$id_produit]['qte']--;
        } else {
            unset($_SESSION['panier'][$id_produit]);
        }
        break;

      case 'supprimer':
        unset($_SESSION['panier'][$id_produit]);
        break;
    }
}

header('Location: ../pages/panier.php');
exit;