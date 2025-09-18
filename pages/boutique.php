<?php
session_start();
require_once('../includes/db.php');

// --- NOUVELLE LOGIQUE OPTIMISÉE ---
// On récupère les IDs de tous les produits favoris de l'utilisateur EN UNE SEULE FOIS.
$favoris_ids = [];
if (isset($_SESSION['user']['id'])) {
    $stmt_favoris = $pdo->prepare("SELECT id_produit FROM favoris WHERE id_utilisateur = ?");
    $stmt_favoris->execute([$_SESSION['user']['id']]);
    $favoris_ids = $stmt_favoris->fetchAll(PDO::FETCH_COLUMN);
}

// Logique de filtre et de tri
$sql = "SELECT * FROM produits";
$params = [];

$categorie_filter = $_GET['categorie'] ?? '';
if ($categorie_filter) {
    $sql .= " WHERE categorie = ?";
    $params[] = $categorie_filter;
}

$search_term = $_GET['search'] ?? '';
if ($search_term) {
    $sql .= (strpos($sql, 'WHERE') === false) ? " WHERE" : " AND";
    $sql .= " (nom LIKE ? OR description LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

$sort_order = $_GET['sort'] ?? 'nouveaute';
switch ($sort_order) {
    case 'prix_asc': $sql .= " ORDER BY prix ASC"; break;
    case 'prix_desc': $sql .= " ORDER BY prix DESC"; break;
    default: $sql .= " ORDER BY id DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT DISTINCT categorie FROM produits ORDER BY categorie ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include('../includes/header.php'); ?>
<link rel="stylesheet" href="/assets/css/boutique.css">

<main class="boutique-container">
  <div class="boutique-header">
    <h1>✨ Notre Collection ✨</h1>
  </div>
  <div class="product-grid">
    <?php if (!empty($produits)): ?>
      <?php foreach ($produits as $produit): 
          // La vérification est maintenant instantanée
          $is_liked = in_array($produit['id'], $favoris_ids);
      ?>
        <div class="product-card">
          <a href="/pages/produit.php?id=<?= htmlspecialchars($produit['id']) ?>" class="product-link">
            <div class="product-image-container"><img src="/uploads/<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>"></div>
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
                    <button type="submit" class="btn-action btn-add">Ajouter au panier</button>
                  <?php else: ?>
                    <button type="submit" name="precommander" value="1" class="btn-action btn-precommander">Précommander</button>
                  <?php endif; ?>
              </form>
              
              <form action="/actions/like_produit.php" method="POST" class="like-form" style="<?= isset($_SESSION['user']) ? '' : 'display:none;' ?>">
                  <input type="hidden" name="id_produit" value="<?= $produit['id'] ?>">
                  <button type="submit" class="btn-action btn-like <?= $is_liked ? 'liked' : '' ?>" title="Ajouter aux favoris">
                      <i class="fa-heart <?= $is_liked ? 'fas' : 'far' ?>"></i>
                  </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
        <p class="no-results">Aucun bijou ne correspond à votre recherche.</p>
    <?php endif; ?>
  </div>
</main>

<?php include('../includes/footer.php'); ?>