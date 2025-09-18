<?php
session_start();
include '../includes/db.php';

// Sécurité : Vérifie si l'utilisateur est un admin connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header('Location: /index.php');
    exit;
}

// --- LOGIQUE DE GESTION ---

// Modifier un témoignage
if (isset($_POST['modifier'])) {
    $id = (int)$_POST['id'];
    $nom_client = trim($_POST['nom_client']);
    $commentaire = trim($_POST['commentaire']);
    $note = (int)$_POST['note'];

    $stmt = $pdo->prepare("UPDATE temoignages SET nom_client = ?, commentaire = ?, note = ? WHERE id = ?");
    $stmt->execute([$nom_client, $commentaire, $note, $id]);
    
    header('Location: temoignages.php?update_success=1');
    exit;
}

// Gérer les actions (Cacher, Afficher, Supprimer)
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'hide') {
        $pdo->prepare("UPDATE temoignages SET est_approuve = 0 WHERE id = ?")->execute([$id]);
    } elseif ($_GET['action'] === 'show') {
        $pdo->prepare("UPDATE temoignages SET est_approuve = 1 WHERE id = ?")->execute([$id]);
    } elseif ($_GET['action'] === 'delete') {
        // Supprimer les images associées
        $stmt = $pdo->prepare("SELECT photos FROM temoignages WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['photos'])) {
            $photos = json_decode($result['photos'], true);
            foreach ($photos as $photo) {
                $file_path = __DIR__ . '/../uploads/temoignages/' . $photo;
                if (file_exists($file_path)) unlink($file_path);
            }
        }
        $pdo->prepare("DELETE FROM temoignages WHERE id = ?")->execute([$id]);
    }
    header('Location: temoignages.php');
    exit;
}

// Récupérer les données pour l'affichage
$temoignages = $pdo->query("SELECT * FROM temoignages ORDER BY date_creation DESC")->fetchAll();
$temoignage_a_modifier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM temoignages WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $temoignage_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">

<main class="admin-container">
    <div class="admin-header">
        <h1>💬 Gestion des Témoignages</h1>
        <a href="dashboard.php" class="btn-back-dashboard">Retour au Dashboard</a>
    </div>

  <div class="admin-grid">
    <div class="table-container">
      <h3>Liste des témoignages</h3>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Commentaire & Photos</th>
            <th>Note</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($temoignages as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['nom_client']) ?></td>
            <td>
                <?= nl2br(htmlspecialchars($t['commentaire'])) ?>
                <?php
                if (!empty($t['photos'])) {
                    $photos = json_decode($t['photos'], true);
                    if ($photos) echo '<div style="margin-top: 10px; display: flex; gap: 5px;"><a href="/uploads/temoignages/' . htmlspecialchars($photos[0]) . '" target="_blank"><img src="/uploads/temoignages/' . htmlspecialchars($photos[0]) . '" width="50" style="border-radius: 4px;"></a></div>';
                }
                ?>
            </td>
            <td><?= str_repeat('⭐', $t['note']) ?></td>
            <td>
              <span class="status-badge <?= $t['est_approuve'] ? 'active' : 'inactive' ?>">
                <?= $t['est_approuve'] ? 'Visible' : 'Caché' ?>
              </span>
            </td>
            <td class="actions">
              <a href="?edit=<?= $t['id'] ?>">Modifier</a>
              <a href="?action=<?= $t['est_approuve'] ? 'hide' : 'show' ?>&id=<?= $t['id'] ?>">
                <?= $t['est_approuve'] ? 'Cacher' : 'Afficher' ?>
              </a>
              <a href="?action=delete&id=<?= $t['id'] ?>" onclick="return confirm('Supprimer ce témoignage ?')">Supprimer</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <aside class="admin-form-container">
      <?php if ($temoignage_a_modifier): ?>
        <h2>Modifier le témoignage #<?= $temoignage_a_modifier['id'] ?></h2>
        <form method="POST">
          <input type="hidden" name="id" value="<?= $temoignage_a_modifier['id'] ?>">
          <div class="form-group">
            <label>Nom du client</label>
            <input type="text" name="nom_client" value="<?= htmlspecialchars($temoignage_a_modifier['nom_client']) ?>" required>
          </div>
          <div class="form-group">
            <label>Commentaire</label>
            <textarea name="commentaire" rows="8" required><?= htmlspecialchars($temoignage_a_modifier['commentaire']) ?></textarea>
          </div>
          <div class="form-group">
            <label>Note</label>
            <select name="note" required>
              <?php for($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= $temoignage_a_modifier['note'] == $i ? 'selected' : '' ?>><?= str_repeat('⭐', $i) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <button type="submit" name="modifier" class="btn-submit">Enregistrer les modifications</button>
          <a href="temoignages.php" style="display:block; text-align:center; margin-top:1rem;">Annuler</a>
        </form>
      <?php else: ?>
        <h2>Gestion</h2>
        <p>Sélectionnez un témoignage dans la liste pour le modifier.</p>
        <p>Les nouveaux témoignages sont automatiquement approuvés et visibles sur le site. Vous pouvez les cacher ou les supprimer ici.</p>
      <?php endif; ?>
    </aside>
  </div>
</main>

<style>
textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
</style>

<?php include '../includes/footer.php'; ?>