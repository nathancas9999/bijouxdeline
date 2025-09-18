<?php
session_start();
require_once('../includes/db.php');

$panier = $_SESSION['panier'] ?? [];
$sous_total = 0;
$total_poids = 0;
$lignes_panier = [];

foreach ($panier as $id => $details) {
    if (!is_numeric($id) || $id <= 0) continue;
    $produit = getProduitById($pdo, $id);
    if ($produit) {
        $qte = $details['qte'];
        $total_poids += ($produit['poids'] ?? 0) * $qte;
        $lignes_panier[] = ['produit' => $produit, 'qte' => $qte, 'total_ligne' => $produit['prix'] * $qte, 'is_precommande' => $details['precommande']];
        $sous_total += $produit['prix'] * $qte;
    }
}

$frais_port = 0;
if ($sous_total > 0) {
    $frais_port = ($sous_total >= 50) ? 0 : (2.50 + ($total_poids / 100) * 0.50);
}
$_SESSION['frais_port'] = $frais_port;

$promo = $_SESSION['promo'] ?? null;
$reduction = 0;
if ($promo) {
    $reduction = ($promo['type'] === 'percent') ? (($sous_total * $promo['valeur']) / 100) : $promo['valeur'];
}
$total_final = $sous_total - $reduction + $frais_port;

$destination_validation = isset($_SESSION['user']) ? '/actions/go_to_payment.php' : '/auth/login.php';
?>

<?php include('../includes/header.php'); ?>
<link rel="stylesheet" href="/assets/css/panier.css">

<style>
  /* --- NOUVEAUX STYLES POUR LE MESSAGE D'ERREUR --- */
  .message-banner.error {
    background-color: #ffebee; /* Rouge très clair */
    color: #c62828; /* Rouge foncé */
    padding: 1.2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    border: 1px solid #ef9a9a; /* Bordure rouge */
    max-width: 600px; /* Limiter la largeur du message */
    margin-left: auto;
    margin-right: auto;
  }
</style>

<main class="panier-container">
  <div class="panier-header"><h1>🛍️ Mon Panier</h1></div>
  
  <?php if (isset($_SESSION['message_panier'])): ?>
    <div class="message-banner <?= htmlspecialchars($_SESSION['message_panier']['type']) ?>">
        <?= htmlspecialchars($_SESSION['message_panier']['text']) ?>
    </div>
    <?php unset($_SESSION['message_panier']); // On supprime le message pour qu'il n'apparaisse qu'une fois ?>
  <?php endif; ?>

  <?php if (empty($lignes_panier)) : ?>
    <div class="panier-vide"><p>Votre panier est vide pour le moment.</p><a href="/pages/boutique.php" class="btn-decouvrir">Découvrir nos bijoux</a></div>
  <?php else : ?>
    <div class="panier-grid">
      <div class="panier-items">
        <?php foreach ($lignes_panier as $ligne) : ?>
        <div class="panier-item">
          <div class="panier-item-img"><img src="/uploads/<?= htmlspecialchars($ligne['produit']['image']) ?>" alt="<?= htmlspecialchars($ligne['produit']['nom']) ?>"></div>
          <div class="panier-item-details">
            <h4><?= htmlspecialchars($ligne['produit']['nom']) ?></h4>
            <p>Prix : <?= number_format($ligne['produit']['prix'], 2, ',', ' ') ?> €</p>
            <?php if ($ligne['is_precommande']): ?><p class="precommande-tag">Précommande</p><?php endif; ?>
            <form action="/actions/maj_panier.php" method="post"><input type="hidden" name="id" value="<?= $ligne['produit']['id'] ?>"><button type="submit" name="action" value="supprimer" class="btn-remove-item">🗑 Supprimer</button></form>
          </div>
          <div class="panier-item-qte">
            <form action="/actions/maj_panier.php" method="post"><input type="hidden" name="id" value="<?= $ligne['produit']['id'] ?>"><button type="submit" name="action" value="moins">-</button><span><?= $ligne['qte'] ?></span><button type="submit" name="action" value="plus">+</button></form>
          </div>
          <div class="panier-item-total"><?= number_format($ligne['total_ligne'], 2, ',', ' ') ?> €</div>
        </div>
        <?php endforeach; ?>
      </div>
      <aside class="panier-recap">
        <h3>Récapitulatif</h3>
        <form action="/actions/appliquer_code.php" method="POST" class="promo-form"><input type="text" name="code_promo" placeholder="Code promo"><button type="submit">OK</button></form>
        <?php if (isset($_SESSION['message_promo'])): ?><div class="message-promo <?= $_SESSION['message_promo']['type'] ?>"><?= htmlspecialchars($_SESSION['message_promo']['text']) ?></div><?php unset($_SESSION['message_promo']); endif; ?>
        <div class="total-summary">
          <div><span>Sous-total</span><span><?= number_format($sous_total, 2, ',', ' ') ?> €</span></div>
          <div><span>Frais de port</span><span><?= ($frais_port > 0) ? number_format($frais_port, 2, ',', ' ') . ' €' : 'Offerts' ?></span></div>
          <?php if ($promo): ?>
          <div class="reduction"><span>Réduction (<?= htmlspecialchars($promo['code']) ?>)</span><span class="reduction-amount">-<?= number_format($reduction, 2, ',', ' ') ?> € <a href="/actions/supprimer_code.php" class="btn-remove-promo" title="Supprimer"><i class="fas fa-times"></i></a></span></div>
          <?php endif; ?>
          <div class="total-final"><span>Total</span><span><?= number_format($total_final, 2, ',', ' ') ?> €</span></div>
        </div>
        <a href="<?= $destination_validation ?>" class="btn-valider">Valider ma commande</a>
      </aside>
    </div>
  <?php endif; ?>
</main>
<?php include('../includes/footer.php'); ?>