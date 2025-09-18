<?php 
include './includes/header.php'; 
include './includes/db.php'; 

// --- CORRECTION DÉFINITIVE DU BUG DE LA PAGE BLANCHE ---
$favoris_ids = [];
if (isset($_SESSION['user']['id'])) {
    try {
        $stmt_favoris = $pdo->prepare("SELECT id_produit FROM favoris WHERE id_utilisateur = ?");
        $stmt_favoris->execute([$_SESSION['user']['id']]);
        $favoris_ids = $stmt_favoris->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // En cas d'erreur (ex: table favoris non créée), on continue sans bloquer la page.
        error_log("Erreur de requête sur la table favoris: " . $e->getMessage());
        $favoris_ids = []; 
    }
}
?>
<link rel="stylesheet" href="/assets/css/home.css">

<main class="home">
  <section class="hero">
    <div class="hero-content">
      <h1>✨ Bijoux de Line ✨</h1>
      <p>Des bijoux raffinés pour sublimer votre élégance.</p>
      <a href="/pages/boutique.php" class="btn">Voir la boutique</a>
    </div>
  </section>

  <section class="section">
    <h2>🌟 Nouveautés</h2>
    <div class="products">
      <?php
        $stmt_produits = $pdo->query("SELECT * FROM produits ORDER BY id DESC LIMIT 3");
        while ($row = $stmt_produits->fetch()) {
          $is_liked = in_array($row['id'], $favoris_ids);
      ?>
        <div class="product">
          <a href="/pages/produit.php?id=<?= $row['id'] ?>" class="product-link">
            <div class="product-image-wrapper">
              <img src="/uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['nom']) ?>">
            </div>
            <h3><?= htmlspecialchars($row['nom']) ?></h3>
          </a>
          <p><?= number_format($row['prix'], 2, ',', ' ') ?> €</p>
          <?php if ($row['stock'] <= 0): ?>
            <span class="out-of-stock-badge">Hors stock</span>
          <?php endif; ?>
          <div class="product-actions">
            <form action="/actions/ajouter_panier.php" method="POST" class="add-to-cart-form">
              <input type="hidden" name="id_produit" value="<?= $row['id'] ?>">
              <?php if ($row['stock'] > 0): ?>
                <button type="submit" class="btn-action btn-add">Ajouter au panier</button>
              <?php else: ?>
                <button type="submit" name="precommander" value="1" class="btn-action btn-precommander">Précommander</button>
              <?php endif; ?>
            </form>
            <form action="/actions/like_produit.php" method="POST" class="like-form" style="<?= isset($_SESSION['user']) ? '' : 'display:none;' ?>">
                <input type="hidden" name="id_produit" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-action btn-like <?= $is_liked ? 'liked' : '' ?>" title="Ajouter aux favoris">
                    <i class="fa-heart <?= $is_liked ? 'fas' : 'far' ?>"></i>
                </button>
            </form>
          </div>
        </div>
      <?php } ?>
    </div>
  </section>

  <section class="section light">
      <h2>💬 Ce que nos client(e)s en pensent</h2>
      <?php
        $temoignages = getTemoignagesApprouves();
      ?>
      <?php if (!empty($temoignages)): ?>
      <div class="carousel-container">
        <div class="carousel-slide">
          <?php foreach ($temoignages as $temoignage): 
            $photos = !empty($temoignage['photos']) ? json_decode($temoignage['photos'], true) : [];
            $has_photo = !empty($photos) && file_exists(__DIR__ . '/uploads/temoignages/' . $photos[0]);
            if ($has_photo):
          ?>
          <div class="testimonial-card">
            <div class="testimonial-image-container">
                <img src="/uploads/temoignages/<?= htmlspecialchars($photos[0]) ?>" alt="Témoignage de <?= htmlspecialchars($temoignage['nom_client']) ?>">
            </div>
            <div class="testimonial-content">
                <div class="testimonial-stars"><?= str_repeat('⭐', $temoignage['note']) ?></div>
                <p class="testimonial-text">"<?= htmlspecialchars($temoignage['commentaire']) ?>"</p>
                <h4 class="testimonial-author">- <?= htmlspecialchars($temoignage['nom_client']) ?></h4>
            </div>
          </div>
          <?php endif; endforeach; ?>
        </div>
        <button class="prev-btn">❮</button>
        <button class="next-btn">❯</button>
      </div>
      <?php else: ?>
        <p>Il n'y a pas encore de témoignages.</p>
      <?php endif; ?>
    </section>
</main>
<?php include './includes/footer.php'; ?>