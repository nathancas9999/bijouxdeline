<?php
session_start();
include '../includes/db.php';

// Sécurité : Vérifie si l'utilisateur est un admin connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    die("⛔ Accès refusé. Admin uniquement.");
}

// Logique pour mettre à jour le statut d'une commande
if (isset($_POST['update_status'])) {
    $commande_id = (int)$_POST['commande_id'];
    $nouveau_statut = $_POST['statut'];

    // Récupérer l'ancien statut et l'ID utilisateur avant la mise à jour
    $stmt_ancien_statut = $pdo->prepare("SELECT statut, id_utilisateur FROM commandes WHERE id = ?");
    $stmt_ancien_statut->execute([$commande_id]);
    $commande_actuelle = $stmt_ancien_statut->fetch(PDO::FETCH_ASSOC);
    $ancien_statut = $commande_actuelle['statut'];
    $user_id = $commande_actuelle['id_utilisateur'];

    // Mettre à jour le statut dans la base de données
    $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $commande_id]);

    // Envoyer un email si la commande est marquée comme "Expédiée" pour la première fois
    if ($nouveau_statut === 'Expédiée' && $ancien_statut !== 'Expédiée') {
        if (function_exists('sendShippingConfirmationEmail')) {
            sendShippingConfirmationEmail($user_id, $commande_id);
        }
    }

    header('Location: commandes.php?id=' . $commande_id . '&update_success=1');
    exit;
}

// --- Logique d'affichage ---
$details_commande = null;
$commande_info = null;

// Filtre par statut
$statut_filter = $_GET['statut'] ?? '';
$sql_where = '';
$params = [];
if (!empty($statut_filter)) {
    $sql_where = "WHERE c.statut = ?";
    $params[] = $statut_filter;
}


if (isset($_GET['id'])) {
    // --- Vue détaillée d'une seule commande ---
    $commande_id = (int)$_GET['id'];
    
    // Récupérer les infos de la commande principale et du client
    $stmt_commande = $pdo->prepare(
        "SELECT c.*, u.nom AS nom_client, u.email AS email_client 
         FROM commandes c 
         JOIN utilisateurs u ON c.id_utilisateur = u.id 
         WHERE c.id = ?"
    );
    $stmt_commande->execute([$commande_id]);
    $commande_info = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    // Récupérer les produits de cette commande
    $stmt_details = $pdo->prepare(
        "SELECT p.nom, p.image, dc.quantite, dc.prix 
         FROM details_commandes dc 
         JOIN produits p ON dc.id_produit = p.id 
         WHERE dc.id_commande = ?"
    );
    $stmt_details->execute([$commande_id]);
    $details_commande = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

} else {
    // --- Vue de la liste de toutes les commandes ---
    $query = "SELECT c.*, u.nom AS nom_client 
              FROM commandes c 
              JOIN utilisateurs u ON c.id_utilisateur = u.id 
              $sql_where
              ORDER BY c.date_commande DESC";
    $stmt_commandes = $pdo->prepare($query);
    $stmt_commandes->execute($params);
    $commandes = $stmt_commandes->fetchAll();
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">

<main class="admin-container">
    <div class="admin-header">
        <h1>📦 Gestion des Commandes</h1>
        <a href="dashboard.php" class="btn-back-dashboard">Retour au Dashboard</a>
    </div>

  <?php if ($details_commande): // Si on est en vue détaillée ?>
    
    <a href="commandes.php" style="text-decoration: none; margin-bottom: 2rem; display: inline-block;">&larr; Retour à toutes les commandes</a>
    <h2>Détail de la Commande #<?= htmlspecialchars($commande_info['id']) ?></h2>

    <div class="admin-grid">
      <div class="table-container">
        <h3>Produits Commandés</h3>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Quantité</th>
              <th>Prix Unitaire</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($details_commande as $item): ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 15px;">
                  <img src="/uploads/<?= htmlspecialchars($item['image']) ?>" alt="" width="50" style="border-radius: 5px;">
                  <strong><?= htmlspecialchars($item['nom']) ?></strong>
                </div>
              </td>
              <td><?= htmlspecialchars($item['quantite']) ?></td>
              <td><?= number_format($item['prix'], 2, ',', ' ') ?> €</td>
              <td><?= number_format($item['prix'] * $item['quantite'], 2, ',', ' ') ?> €</td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #f9f9f9;">
                <td colspan="3" style="text-align: right;">Total de la commande :</td>
                <td><?= number_format($commande_info['total'], 2, ',', ' ') ?> €</td>
            </tr>
          </tbody>
        </table>
      </div>

      <aside class="admin-form-container">
        <h3>Informations</h3>
        <p><strong>Client :</strong> <?= htmlspecialchars($commande_info['nom_client']) ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($commande_info['email_client']) ?></p>
        <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($commande_info['date_commande'])) ?></p>
        <p><strong>Adresse de livraison :</strong><br><?= nl2br(htmlspecialchars($commande_info['adresse_livraison'])) ?></p>
        
        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #eee;">

        <h3>Statut de la commande</h3>
        <form method="POST">
          <input type="hidden" name="commande_id" value="<?= $commande_info['id'] ?>">
          <div class="form-group">
            <select name="statut" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
              <option value="En cours" <?= $commande_info['statut'] == 'En cours' ? 'selected' : '' ?>>En cours</option>
              <option value="Expédiée" <?= $commande_info['statut'] == 'Expédiée' ? 'selected' : '' ?>>Expédiée</option>
              <option value="Livrée" <?= $commande_info['statut'] == 'Livrée' ? 'selected' : '' ?>>Livrée</option>
              <option value="Annulée" <?= $commande_info['statut'] == 'Annulée' ? 'selected' : '' ?>>Annulée</option>
            </select>
          </div>
          <button type="submit" name="update_status" class="btn-submit">Mettre à jour</button>
        </form>
      </aside>
    </div>

  <?php else: // Sinon, on affiche la liste des commandes ?>
    <div class="table-container">
        <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3>Toutes les commandes</h3>
            <form method="GET" class="filter-form" style="display: flex; gap: 10px; align-items: center;">
                <label for="statut">Filtrer par statut:</label>
                <select name="statut" id="statut" onchange="this.form.submit()" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <option value="">Tous</option>
                    <option value="En cours" <?= $statut_filter == 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Expédiée" <?= $statut_filter == 'Expédiée' ? 'selected' : '' ?>>Expédiée</option>
                    <option value="Livrée" <?= $statut_filter == 'Livrée' ? 'selected' : '' ?>>Livrée</option>
                    <option value="Annulée" <?= $statut_filter == 'Annulée' ? 'selected' : '' ?>>Annulée</option>
                </select>
            </form>
        </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Date</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($commandes as $c): ?>
          <tr>
            <td>#<?= htmlspecialchars($c['id']) ?></td>
            <td><?= htmlspecialchars($c['nom_client']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($c['date_commande'])) ?></td>
            <td><?= number_format($c['total'], 2, ',', ' ') ?> €</td>
            <td><span class="status-badge" style="background-color: <?= 
                match($c['statut']) {
                    'En cours' => '#ff9800',
                    'Expédiée' => '#2196F3',
                    'Livrée' => '#4CAF50',
                    'Annulée' => '#f44336',
                    default => '#777'
                } 
            ?>;"><?= htmlspecialchars($c['statut']) ?></span></td>
            <td><a href="?id=<?= $c['id'] ?>" class="btn-view" style="padding: 5px 10px; background: #eee; border-radius: 5px; text-decoration: none; color: #333;">Voir</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>