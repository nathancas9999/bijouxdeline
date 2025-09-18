<?php
session_start();
include './includes/db.php';

// 1. Sécurité : Si l'utilisateur n'est pas connecté, le rediriger.
if (!isset($_SESSION['user'])) {
  header('Location: /auth/login.php');
  exit;
}

$user_id = $_SESSION['user']['id'];
$message = null;
$message_type = '';

// Message de succès après l'envoi d'un témoignage
if (isset($_GET['testimonial_success']) && $_GET['testimonial_success'] == 1) {
    $message = "Merci ! Votre témoignage a bien été envoyé.";
    $message_type = 'success';
}

// 2. Logique de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!empty($_POST['nom']) && $_POST['nom'] !== $_SESSION['user']['nom']) {
        $stmt_nom = $pdo->prepare("UPDATE utilisateurs SET nom = ? WHERE id = ?");
        $stmt_nom->execute([$_POST['nom'], $user_id]);
        $_SESSION['user']['nom'] = $_POST['nom'];
    }

    $stmt_adresse = $pdo->prepare("UPDATE utilisateurs SET adresse = ?, code_postal = ?, ville = ?, pays = ? WHERE id = ?");
    $stmt_adresse->execute([$_POST['adresse'], $_POST['code_postal'], $_POST['ville'], $_POST['pays'], $user_id]);

    if (!empty($_POST['mot_de_passe'])) {
        if ($_POST['mot_de_passe'] === $_POST['mot_de_passe_confirm']) {
            if (strlen($_POST['mot_de_passe']) >= 8) {
                $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
                $stmt_mdp = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                $stmt_mdp->execute([$hash, $user_id]);
                $message = "Vos informations ont été mises à jour avec succès.";
                $message_type = 'success';
            } else {
                $message = "Erreur : Le mot de passe doit contenir au moins 8 caractères.";
                $message_type = 'error';
            }
        } else {
            $message = "Erreur : Les mots de passe ne correspondent pas.";
            $message_type = 'error';
        }
    } else {
        if (empty($message)) {
            $message = "Vos informations ont été mises à jour avec succès.";
            $message_type = 'success';
        }
    }
}

// 3. Récupération des données
$stmt_user = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$points_fidelite = $user_data['points_fidelite'] ?? 0;

$stmt_codes = $pdo->prepare("SELECT * FROM promotions WHERE id_utilisateur = ? AND is_active = 1 AND times_used < max_uses ORDER BY date_expiration DESC");
$stmt_codes->execute([$user_id]);
$codes_fidelite = $stmt_codes->fetchAll(PDO::FETCH_ASSOC);

$stmt_commandes = $pdo->prepare("SELECT * FROM commandes WHERE id_utilisateur = ? ORDER BY date_commande DESC");
$stmt_commandes->execute([$user_id]);
$commandes = $stmt_commandes->fetchAll();

$stmt_favoris = $pdo->prepare("SELECT p.* FROM favoris f JOIN produits p ON f.id_produit = p.id WHERE f.id_utilisateur = ? ORDER BY f.date_ajout DESC");
$stmt_favoris->execute([$user_id]);
$favoris = $stmt_favoris->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include './includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/profil.css">
<link rel="stylesheet" href="/assets/css/boutique.css">

<main class="profil-container">

    <?php if ($message): ?>
        <div class="message-banner <?= $message_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="profil-card" style="margin-bottom: 2rem; text-align:center;">
      <h2>Une minute pour nous aider ?</h2>
      <p>Votre avis nous est précieux pour nous améliorer et guider les autres client(e)s.</p>
      <a href="/pages/laisser-temoignage.php" class="btn" style="display: inline-block; text-decoration: none; padding: 12px 24px;">Laisser un avis</a>
    </div>

  <div class="profil-grid-top">
    <div class="fidelite-card">
        <h2>💖 Mon Espace Fidélité</h2>
        <p>Vous avez actuellement</p>
        <div class="points-display"><?= htmlspecialchars($points_fidelite) ?></div>
        <p>points !</p>
        <small>1 point est gagné pour chaque commande.</small>
    </div>

    <div class="codes-fidelite-card">
      <h2>🎁 Mes Codes Promo</h2>
      <?php if ($codes_fidelite): ?>
        <p>Voici les codes que vous avez gagnés. Utilisez-les dans votre panier !</p>
        <div class="codes-list">
          <?php foreach($codes_fidelite as $code): ?>
            <div class="code-item">
              <span class="code-value"><?= htmlspecialchars($code['code']) ?></span>
              <span class="code-details">
                -<?= number_format($code['valeur'], 2, ',', ' ') ?> € | Expire le <?= date('d/m/Y', strtotime($code['date_expiration'])) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>Vous n'avez pas de code promo de fidélité actif pour le moment.</p>
      <?php endif; ?>
    </div>
  </div>



  <div class="profil-card">
    <h1>👤 Mon Profil & Adresse</h1>
    <form method="POST" class="profil-form">
      <input type="hidden" name="update_profile" value="1">
      <div class="form-group"><label for="nom">Prénom</label><input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user_data['nom']) ?>"></div>
      <div class="form-group"><label for="email">Adresse e-mail</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" disabled></div>
      <hr style="border:none; border-top:1px solid #eee; margin: 2rem 0;">
      <h4>Adresse de livraison par défaut</h4>
      <div class="form-group"><label for="adresse">Adresse</label><input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($user_data['adresse'] ?? '') ?>"></div>
      <div style="display: flex; gap: 1rem;"><div class="form-group" style="flex: 1;"><label for="code_postal">Code Postal</label><input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($user_data['code_postal'] ?? '') ?>"></div><div class="form-group" style="flex: 2;"><label for="ville">Ville</label><input type="text" id="ville" name="ville" value="<?= htmlspecialchars($user_data['ville'] ?? '') ?>"></div></div>
      <div class="form-group"><label for="pays">Pays</label><input type="text" id="pays" name="pays" value="<?= htmlspecialchars($user_data['pays'] ?? '') ?>"></div>
      <hr style="border:none; border-top:1px solid #eee; margin: 2rem 0;">
      <h4>Changer de mot de passe</h4>
      <div class="form-group"><label for="mot_de_passe">Nouveau mot de passe</label><input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Laisser vide pour ne pas changer (8 caractères min.)"></div>
      <div class="form-group"><label for="mot_de_passe_confirm">Confirmer le nouveau mot de passe</label><input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" placeholder="Retapez votre mot de passe"></div>
      <button type="submit" class="btn-submit" style="width:100%;">Mettre à jour mes informations</button>
    </form>
  </div>

  <div class="commandes-card">
    <h2>📦 Mes Commandes</h2>
    <?php if ($commandes): ?>
      <div class="commandes-list">
        <?php foreach($commandes as $commande): ?>
          <div class="commande">
            <div class="commande-header" onclick="toggleDetails(this)">
              <div class="commande-header-info"><span><strong>Commande #<?= $commande['id'] ?></strong></span><span><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></span><span>Total : <strong><?= number_format($commande['total'], 2, ',', ' ') ?> €</strong></span></div>
              <div><span class="statut <?= strtolower(str_replace(' ', '-', $commande['statut'])) ?>"><?= htmlspecialchars(ucfirst($commande['statut'])) ?></span><span style="margin-left:10px; transition: transform 0.3s;">▼</span></div>
            </div>
            <div class="commande-details">
              <?php
                $stmt_details = $pdo->prepare("SELECT p.nom, p.image, dc.quantite, dc.prix, dc.statut_item FROM details_commandes dc JOIN produits p ON p.id = dc.id_produit WHERE dc.id_commande = ?");
                $stmt_details->execute([$commande['id']]);
                $details = $stmt_details->fetchAll();
              ?>
              <?php foreach ($details as $produit): ?>
                <div class="produit-commande">
                  <img src="/uploads/<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
                  <div class="produit-commande-info">
                    <strong><?= htmlspecialchars($produit['nom']) ?></strong><br>
                    <small>Quantité : <?= $produit['quantite'] ?></small>
                    <?php if ($produit['statut_item'] == 'Precommande'): ?>
                        <br><small style="color: #ff9800; font-weight:bold;">(Précommande)</small>
                    <?php endif; ?>
                  </div>
                  <div class="produit-commande-prix"><strong><?= number_format($produit['prix'] * $produit['quantite'], 2, ',', ' ') ?> €</strong></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>Vous n'avez encore passé aucune commande.</p>
    <?php endif; ?>
  </div>
</main>

<style>
.btn-submit { background-color: #d46a92; color: white; padding: 12px 20px; border: none; border-radius: 25px; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
.btn-submit:hover { background-color: #a75d67; }
.commande-details { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-out, padding 0.5s ease-out; padding: 0 1.5rem; }
.message-banner { padding: 1rem; margin-bottom: 2rem; border-radius: 8px; text-align: center; }
.message-banner.success { background-color: #e8f5e9; color: #388e3c; }
.message-banner.error { background-color: #ffcdd2; color: #c62828; }
</style>

<?php include './includes/footer.php'; ?>