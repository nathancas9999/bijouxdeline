<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

$id_produit = (int)($_POST['id_produit'] ?? 0);
$is_precommande = isset($_POST['precommander']);

if ($id_produit <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_product']);
    exit;
}

// Si c'est une précommande, on ne vérifie pas le stock et on ajoute directement
if ($is_precommande) {
    if (!isset($_SESSION['panier'][$id_produit])) {
        $_SESSION['panier'][$id_produit] = ['qte' => 0, 'precommande' => true];
    }
    $_SESSION['panier'][$id_produit]['qte']++;
} else {
    // --- NOUVELLE LOGIQUE DE VÉRIFICATION DU STOCK ---
    
    // 1. Récupérer le stock actuel du produit depuis la base de données
    $stmt = $pdo->prepare("SELECT stock FROM produits WHERE id = ?");
    $stmt->execute([$id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        echo json_encode(['success' => false, 'error' => 'product_not_found']);
        exit;
    }
    $stock_actuel = $produit['stock'];

    // 2. Vérifier combien d'exemplaires de ce produit sont déjà dans le panier de l'utilisateur
    $qte_deja_au_panier = $_SESSION['panier'][$id_produit]['qte'] ?? 0;

    // 3. Comparer
    if ($stock_actuel > $qte_deja_au_panier) {
        // Il reste du stock, on peut ajouter
        if (!isset($_SESSION['panier'][$id_produit])) {
            $_SESSION['panier'][$id_produit] = ['qte' => 0, 'precommande' => false];
        }
        $_SESSION['panier'][$id_produit]['qte']++;
    } else {
        // Le stock est déjà entièrement dans le panier de cet utilisateur (ou d'autres), on refuse l'ajout
        echo json_encode(['success' => false, 'error' => 'stock_insufficient']);
        exit;
    }
}


// 4. On calcule et renvoie le nombre total d'articles dans le panier
$panier_count = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $details) {
        $panier_count += $details['qte'];
    }
}

echo json_encode(['success' => true, 'panier_count' => $panier_count]);
exit;