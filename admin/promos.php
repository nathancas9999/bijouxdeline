<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    die("⛔ Accès refusé. Admin uniquement.");
}

// AJOUTER
if (isset($_POST['ajouter'])) {
    $stmt = $pdo->prepare("INSERT INTO promotions (code, type, valeur, date_expiration, max_uses) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['code'],
        $_POST['type'],
        $_POST['valeur'],
        $_POST['date_expiration'],
        $_POST['max_uses']
    ]);
    header('Location: promos.php');
    exit;
}

// MODIFIER
if (isset($_POST['modifier'])) {
    $stmt = $pdo->prepare("UPDATE promotions SET code = ?, type = ?, valeur = ?, date_expiration = ?, max_uses = ? WHERE id = ?");
    $stmt->execute([
        $_POST['code'],
        $_POST['type'],
        $_POST['valeur'],
        $_POST['date_expiration'],
        $_POST['max_uses'],
        $_POST['id']
    ]);
    header('Location: promos.php');
    exit;
}

// ACTIONS
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'delete') {
        $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
    }
    header('Location: promos.php');
    exit;
}

$promos = $pdo->query(
    "SELECT p.*, u.nom AS nom_utilisateur
     FROM promotions p
     LEFT JOIN utilisateurs u ON p.id_utilisateur = u.id
     ORDER BY p.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$promo_a_modifier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $promo_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">

<main class="admin-container">
  <div class="admin-header">
      <h1>🎟️ Gestion des Codes Promo</h1>
      <a href="dashboard.php" class="btn-back-dashboard">Retour au Dashboard</a>
  </div>

  <div class="admin-grid">
    <div class="table-container">
      <h3>Liste des codes</h3>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Code</th><th>Client</th><th>Réduction</th><th>Utilisation</th><th>Statut</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($promos as $p): ?>
          <tr style="<?= $p['id_utilisateur'] ? 'background-color: #fffafc;' : '' ?>">
            <td><strong><?= htmlspecialchars($p['code']) ?></strong></td>
            <td><?= $p['id_utilisateur'] ? '<em>'.htmlspecialchars($p['nom_utilisateur']).' (Fidélité)</em>' : '<span style="color:#aaa;">Manuel</span>' ?></td>
            <td><?= htmlspecialchars($p['valeur']) ?><?= ($p['type'] == 'percent') ? '%' : ' €' ?></td>
            <td><?= $p['times_used'] ?> / <?= $p['max_uses'] ?></td>
            <td>
              <span class="status-badge <?= $p['is_active'] && ($p['times_used'] < $p['max_uses']) ? 'active' : 'inactive' ?>">
                <?= $p['is_active'] && ($p['times_used'] < $p['max_uses']) ? 'Actif' : 'Épuisé/Inactif' ?>
              </span>
            </td>
            <td class="actions">
              <a href="?edit=<?= $p['id'] ?>#form-section" title="Modifier">✏️</a>
              <a href="?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Vraiment supprimer ce code ?')" title="Supprimer">🗑️</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <aside class="admin-form-container" id="form-section">
      <h2><?= $promo_a_modifier ? 'Modifier le code' : 'Ajouter un code promo' ?></h2>
      <form method="POST" action="promos.php" class="admin-form">
        <?php if ($promo_a_modifier): ?><input type="hidden" name="id" value="<?= $promo_a_modifier['id'] ?>"><?php endif; ?>
        <div class="form-group"><label>Code</label><input type="text" name="code" value="<?= htmlspecialchars($promo_a_modifier['code'] ?? '') ?>" required></div>
        <div class="form-group"><label>Type de réduction</label>
            <select name="type">
                <option value="percent" <?= ($promo_a_modifier['type'] ?? '') == 'percent' ? 'selected' : '' ?>>Pourcentage (%)</option>
                <option value="fixed" <?= ($promo_a_modifier['type'] ?? '') == 'fixed' ? 'selected' : '' ?>>Montant fixe (€)</option>
            </select>
        </div>
        <div class="form-group"><label>Valeur (ex: 15 pour % ou 10 pour €)</label><input type="number" name="valeur" step="0.01" value="<?= htmlspecialchars($promo_a_modifier['valeur'] ?? '') ?>" required></div>
        <div class="form-group"><label>Date d'expiration</label><input type="date" name="date_expiration" value="<?= htmlspecialchars($promo_a_modifier['date_expiration'] ?? '') ?>" required></div>
        <div class="form-group"><label>Utilisations max</label><input type="number" name="max_uses" value="<?= htmlspecialchars($promo_a_modifier['max_uses'] ?? '1') ?>" required min="1"></div>
        <button type="submit" name="<?= $promo_a_modifier ? 'modifier' : 'ajouter' ?>" class="btn-submit"><?= $promo_a_modifier ? 'Enregistrer' : 'Ajouter' ?></button>
        <?php if ($promo_a_modifier): ?><a href="promos.php" class="btn-cancel">Annuler</a><?php endif; ?>
      </form>
    </aside>
  </div>
</main>
<?php include '../includes/footer.php'; ?>