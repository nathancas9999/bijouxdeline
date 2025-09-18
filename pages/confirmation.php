<?php
session_start();
// On inclut la connexion à la BDD et toutes les fonctions
include '../includes/db.php'; 

// 1. Sécurité : si l'utilisateur n'est pas connecté ou si son panier est vide, on le renvoie à l'accueil.
if (!isset($_SESSION['user']) || empty($_SESSION['panier'])) {
    header('Location: /index.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$panier = $_SESSION['panier'];
$message_recompense = null;

try {
    // On commence une transaction pour s'assurer que toutes les étapes s'exécutent correctement
    $pdo->beginTransaction();

    // 2. On collecte et recalcule toutes les informations de la commande
    $sous_total = 0;
    $details_pour_bdd = [];
    $details_pour_email = [];

    foreach ($panier as $id => $details) {
        $produit = getProduitById($pdo, $id);
        if ($produit) {
            $qte = $details['qte'];
            $is_precommande = $details['precommande'];
            $sous_total += $produit['prix'] * $qte;
            
            $details_pour_bdd[] = [
                'id_produit' => $id, 
                'quantite' => $qte, 
                'prix' => $produit['prix'],
                'statut_item' => $is_precommande ? 'Precommande' : 'Achete'
            ];
            $details_pour_email[] = [
                'nom' => $produit['nom'], 
                'image' => $produit['image'], 
                'quantite' => $qte, 
                'prix' => $produit['prix'],
                'is_precommande' => $is_precommande
            ];
        }
    }

    $frais_port = $_SESSION['frais_port'] ?? 0;
    $reduction = 0;
    $code_promo = '';
    if (isset($_SESSION['promo_utilise'])) {
        $promo = $_SESSION['promo_utilise'];
        $code_promo = $promo['code'];
        $reduction = ($promo['type'] === 'percent') ? (($sous_total * $promo['valeur']) / 100) : $promo['valeur'];
    }
    $total_final = $sous_total - $reduction + $frais_port;

    // 3. On enregistre la commande principale
    $adresse_livraison_str = implode(', ', $_SESSION['adresse_livraison'] ?? []);
    $stmt = $pdo->prepare("INSERT INTO commandes (id_utilisateur, total, adresse_livraison, statut) VALUES (?, ?, ?, 'En cours')");
    $stmt->execute([$user_id, $total_final, $adresse_livraison_str]);
    $last_order_id = $pdo->lastInsertId();

    // 4. On enregistre les détails de la commande (avec la colonne statut_item)
    $stmt_details = $pdo->prepare("INSERT INTO details_commandes (id_commande, id_produit, quantite, prix, statut_item) VALUES (?, ?, ?, ?, ?)");
    foreach ($details_pour_bdd as $item) {
        $stmt_details->execute([$last_order_id, $item['id_produit'], $item['quantite'], $item['prix'], $item['statut_item']]);
    }

    // 5. On met à jour le stock
    $stmt_stock = $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?");
    foreach ($details_pour_bdd as $item) {
        if ($item['statut_item'] === 'Achete') {
            $stmt_stock->execute([$item['quantite'], $item['id_produit']]);
        }
    }

    // 6. On met à jour le code promo si utilisé
    if (!empty($code_promo)) {
        $stmt_promo = $pdo->prepare("UPDATE promotions SET times_used = times_used + 1 WHERE code = ?");
        $stmt_promo->execute([$code_promo]);
    }

    // 7. On ajoute le point de fidélité
    $points_gagnes = 1;
    $stmt_pts = $pdo->prepare("UPDATE utilisateurs SET points_fidelite = points_fidelite + ? WHERE id = ?");
    $stmt_pts->execute([$points_gagnes, $user_id]);

    // Si tout s'est bien passé, on valide la transaction
    $pdo->commit();

    // 8. On envoie l'e-mail de confirmation
    $orderDataForEmail = [
        'details' => $details_pour_email,
        'sous_total' => $sous_total,
        'reduction' => $reduction,
        'frais_port' => $frais_port,
        'code_promo' => $code_promo,
        'total_final' => $total_final,
        'adresse_livraison' => $_SESSION['adresse_livraison']
    ];
    sendOrderConfirmationEmail($user_id, $last_order_id, $orderDataForEmail, $pdo);

    // 9. On vérifie la récompense de fidélité
    if (checkAndGenerateFidelityReward($user_id)) {
        $message_recompense = "Félicitations ! Un code promo de 10€ a été ajouté à votre compte !";
    }

    // 10. On nettoie la session
    unset($_SESSION['panier'], $_SESSION['promo'], $_SESSION['promo_utilise'], $_SESSION['adresse_livraison'], $_SESSION['frais_port']);

} catch (Exception $e) {
    $pdo->rollBack();
    die("Une erreur critique est survenue. Contactez le support. Erreur : " . $e->getMessage());
}

?>
<?php include '../includes/header.php'; ?>

<main class="container" style="text-align: center; padding: 120px 2rem 4rem;">
  <h1>🎉 Merci pour votre commande !</h1>
  <p>Votre commande #<?= htmlspecialchars($last_order_id) ?> a bien été enregistrée. Vous allez recevoir un e-mail de confirmation.</p>
  
  <?php if (isset($points_gagnes) && $points_gagnes > 0): ?>
    <p>Vous avez gagné <strong><?= $points_gagnes ?> point</strong> de fidélité !</p>
  <?php endif; ?>
  
  <?php if ($message_recompense): ?>
    <div style="padding: 1rem; background-color: #e8f5e9; color: #388e3c; border-radius: 8px; margin-top: 1.5rem;">
        <?= htmlspecialchars($message_recompense) ?>
    </div>
  <?php endif; ?>

  <div style="margin-top: 2rem;">
    <a href="/pages/boutique.php" class="btn">Continuer mes achats</a>
    <a href="/profil.php" class="btn-secondary" style="margin-left: 1rem; text-decoration:none; padding: 12px 24px; border-radius: 50px;">Voir mes commandes</a>
  </div>
</main>

<?php include '../includes/footer.php'; ?>