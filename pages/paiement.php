<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once('../includes/db.php');

// 1. Sécurité et redirection
if (!isset($_SESSION['user']) || empty($_SESSION['panier'])) {
    header('Location: /pages/panier.php');
    exit;
}

// 2. Calcul du total (CORRIGÉ)
$panier = $_SESSION['panier'];
$lignes_panier = [];
$sous_total = 0;

// On parcourt le panier correctement
foreach ($panier as $id => $details) {
    $produit = getProduitById($pdo, $id);
    if ($produit) {
        $qte = $details['qte'];
        $lignes_panier[] = ['produit' => $produit, 'qte' => $qte, 'is_precommande' => $details['precommande'], 'total_ligne' => $produit['prix'] * $qte];
        $sous_total += $produit['prix'] * $qte;
    }
}

// On récupère les frais de port depuis la session (calculés dans panier.php)
$frais_port = $_SESSION['frais_port'] ?? 0;

// On applique la promotion correctement
$promo = $_SESSION['promo'] ?? null;
$reduction = 0;
if ($promo) {
    if ($promo['type'] === 'percent') {
        $reduction = ($sous_total * $promo['valeur']) / 100;
    } else { // 'fixed'
        $reduction = $promo['valeur'];
    }
}

// On calcule le total final CORRECT
$total_final = $sous_total - $reduction + $frais_port;

// On stocke le total final en session pour le script de création de transaction PayPal
$_SESSION['last_order_total'] = $total_final;
?>

<?php include '../includes/header.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(PAYPAL_CLIENT_ID) ?>&currency=EUR"></script>

<style>
  .paiement-container { max-width: 1000px; margin: 120px auto 40px; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: flex-start; }
  .recap-commande, .options-paiement { background: #fffafc; border-radius: 12px; padding: 2rem; border: 1px solid #fdeef4; }
  h2 { font-family: 'Edu NSW ACT Cursive', cursive; color: #a75d67; margin-top: 0; }
  .produit-recap { display: flex; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid #eee; }
  .produit-recap:last-child { border-bottom: none; }
  .produit-recap img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
  .produit-recap-info { flex-grow: 1; }
  .total-summary { font-size: 1rem; margin-top: 1.5rem; }
  .total-summary > div { display: flex; justify-content: space-between; padding: 0.5rem 0; }
  .total-summary .total-final { padding-top: 1rem; margin-top: 0.5rem; border-top: 1px solid #eee; font-weight: bold; font-size: 1.3rem; color: #a75d67; }
  @media (max-width: 768px) { .paiement-container { grid-template-columns: 1fr; } }
</style>

<main class="paiement-container">
  <div class="recap-commande">
    <h2>📝 Récapitulatif Final</h2>
    <?php foreach ($lignes_panier as $ligne): ?>
    <div class="produit-recap">
        <img src="/uploads/<?= htmlspecialchars($ligne['produit']['image']) ?>" alt="<?= htmlspecialchars($ligne['produit']['nom']) ?>">
        <div class="produit-recap-info">
            <strong><?= htmlspecialchars($ligne['produit']['nom']) ?></strong> (x<?= $ligne['qte'] ?>)
            <?php if ($ligne['is_precommande']): ?><br><small style="color:#ff9800;font-weight:bold;">(Précommande)</small><?php endif; ?>
        </div>
        <div class="produit-recap-prix"><?= number_format($ligne['total_ligne'], 2, ',', ' ') ?> €</div>
    </div>
    <?php endforeach; ?>

    <div class="total-summary">
      <div><span>Sous-total :</span><span><?= number_format($sous_total, 2, ',', ' ') ?> €</span></div>
      <div><span>Frais de port :</span><span><?= ($frais_port > 0) ? number_format($frais_port, 2, ',', ' ') . ' €' : 'Offerts' ?></span></div>
      <?php if ($promo): ?>
      <div style="color: #388e3c;"><span>Réduction (<?= htmlspecialchars($promo['code']) ?>) :</span><span>-<?= number_format($reduction, 2, ',', ' ') ?> €</span></div>
      <?php endif; ?>
      <div class="total-final"><span>Total à payer :</span><span><?= number_format($total_final, 2, ',', ' ') ?> €</span></div>
    </div>
  </div>

  <div class="options-paiement">
    <h2>💳 Paiement sécurisé</h2>
    <div id="paypal-button-container"></div>
    <div id="error-container" style="color: red; text-align: center; margin-top: 10px;"></div>
  </div>
</main>

<script>
  paypal.Buttons({
    createOrder: function(data, actions) {
      return fetch('/actions/create-paypal-transaction.php', { method: 'post' })
        .then(res => res.json())
        .then(orderData => {
          if (orderData.id) return orderData.id;
          document.getElementById('error-container').innerText = 'Impossible de lancer le paiement. Veuillez réessayer.';
          return null;
        });
    },
    onApprove: function(data, actions) {
      return fetch('/actions/paypal_capture_order.php', {
        method: 'post',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orderID: data.orderID })
      }).then(res => res.json())
      .then(orderData => {
        if (orderData.success) {
          window.location.href = '/pages/confirmation.php';
        } else {
          alert('Une erreur est survenue lors de la validation du paiement.');
        }
      });
    }
  }).render('#paypal-button-container');
</script>

<?php include '../includes/footer.php'; ?>