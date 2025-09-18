<?php
session_start();
require_once('../includes/db.php');

$id_produit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_produit) { header('Location: /pages/boutique.php'); exit; }

$produit = getProduitById($pdo, $id_produit);
if (!$produit) { header('Location: /pages/boutique.php'); exit; }

$is_liked = false;
if (isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
    $stmt->execute([$_SESSION['user']['id'], $id_produit]);
    if ($stmt->fetchColumn() > 0) $is_liked = true;
}
?>
<?php include('../includes/header.php'); ?>
<link rel="stylesheet" href="/assets/css/produit-detail.css?v=<?php echo time(); ?>">

<main class="produit-detail-container">
    <div class="produit-detail-grid">
        <div class="produit-image-container">
            <img src="/uploads/<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
        </div>

        <div class="produit-info-container">
            <div>
                <h1><?= htmlspecialchars($produit['nom']) ?></h1>
                <p class="prix"><?= number_format($produit['prix'], 2, ',', ' ') ?> €</p>
                <div class="description-wrapper">
                    <div class="description">
                        <?= nl2br(htmlspecialchars($produit['description'])) ?>
                    </div>
                </div>
            </div>

            <div class="actions-wrapper">
                <?php if ($produit['stock'] > 0): ?>
                    <p class="stock-info en-stock">En stock</p>
                <?php else: ?>
                    <p class="stock-info hors-stock">Hors stock</p>
                <?php endif; ?>

                <div class="actions-container">
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
                        <button type="submit" class="btn-action btn-like <?= $is_liked ? 'liked' : '' ?>" title="Ajouter à mes favoris">
                            <i class="fa-heart <?= $is_liked ? 'fas' : 'far' ?>"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>