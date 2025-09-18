<?php
session_start();
include '../includes/db.php';

// 1. Sécurité : Si l'utilisateur n'est pas connecté, on le redirige.
if (!isset($_SESSION['user'])) {
  header('Location: /auth/login.php');
  exit;
}

$user_id = $_SESSION['user']['id'];

// 2. Récupérer les produits favoris de l'utilisateur
try {
    $stmt_favoris = $pdo->prepare(
        "SELECT p.* FROM favoris f 
         JOIN produits p ON f.id_produit = p.id 
         WHERE f.id_utilisateur = ? 
         ORDER BY f.date_ajout DESC"
    );
    $stmt_favoris->execute([$user_id]);
    $favoris = $stmt_favoris->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gérer l'erreur si la table n'existe pas ou autre problème
    error_log("Erreur de requête sur la table favoris: " . $e->getMessage());
    $favoris = []; // On initialise un tableau vide pour éviter une erreur d'affichage
}

// On récupère également la liste des IDs favoris pour l'état des boutons "J'aime"
$favoris_ids = array_column($favoris, 'id');
?>

<?php include('../includes/header.php'); ?>
<link rel="stylesheet" href="/assets/css/boutique.css"> 

<main class="boutique-container">
    <div class="boutique-header">
        <h1>❤️ Mes Favoris</h1>
    </div>

    <?php if (empty($favoris)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: #fffafc; border-radius: 12px;">
            <p style="font-size: 1.2rem; color: #888;">Vous n'avez pas encore de bijoux dans vos favoris.</p>
            <p>Cliquez sur le cœur d'un produit pour l'ajouter à votre sélection !</p>
            <a href="/pages/boutique.php" class="btn" style="margin-top: 1.5rem; text-decoration: none; display: inline-block;">Découvrir la collection</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach($favoris as $produit): 
                // Pour chaque produit, on sait qu'il est "liké"
                $is_liked = true; 
            ?>
                <div class="product-card">
                    <a href="/pages/produit.php?id=<?= htmlspecialchars($produit['id']) ?>" class="product-link">
                        <div class="product-image-container">
                            <img src="/uploads/<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
                        </div>
                    </a>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($produit['nom']) ?></h3>
                        <p class="price"><?= number_format($produit['prix'], 2, ',', ' ') ?> €</p>
                        <?php if ($produit['stock'] <= 0): ?>
                            <span class="out-of-stock-badge">Hors stock</span>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <form action="/actions/ajouter_panier.php" method="POST" class="add-to-cart-form">
                                <input type="hidden" name="id_produit" value="<?= $produit['id'] ?>">
                                <?php if ($produit['stock'] > 0): ?>
                                    <button type="submit" class="btn-action btn-add">Ajouter</button>
                                <?php else: ?>
                                    <button type="submit" name="precommander" value="1" class="btn-action btn-precommander">Précommander</button>
                                <?php endif; ?>
                            </form>
                            <form action="/actions/like_produit.php" method="POST" class="like-form">
                                <input type="hidden" name="id_produit" value="<?= $produit['id'] ?>">
                                <button type="submit" class="btn-action btn-like <?= $is_liked ? 'liked' : '' ?>" title="Retirer des favoris">
                                    <i class="fa-heart <?= $is_liked ? 'fas' : 'far' ?>"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include('../includes/footer.php'); ?>