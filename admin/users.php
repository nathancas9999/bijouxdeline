<?php
session_start();
include '../includes/db.php'; // Inclut la connexion ET la fonction checkAndGenerateFidelityReward

// Sécurité : Vérifie si l'utilisateur est un admin connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    die("⛔ Accès refusé. Admin uniquement.");
}

// --- LOGIQUE DE GESTION DES UTILISATEURS ---

// Gérer la modification d'un utilisateur depuis le formulaire principal
if (isset($_POST['modifier_utilisateur'])) {
    $id = (int)$_POST['id'];
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // On ne peut pas retirer le statut admin à soi-même
    if ($id === $_SESSION['user']['id']) {
        $is_admin = 1;
    }

    if (!empty($nom) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Mettre à jour les informations de base
        $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, is_admin = ? WHERE id = ?");
        $stmt->execute([$nom, $email, $is_admin, $id]);

        // Mettre à jour le mot de passe seulement s'il est fourni
        if (!empty($_POST['mot_de_passe'])) {
            $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
            $stmt_mdp = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            $stmt_mdp->execute([$hash, $id]);
        }
        header('Location: users.php?update_success=1');
        exit;
    } else {
        $error = "Veuillez vérifier les informations saisies.";
    }
}


// Gérer la mise à jour des points de fidélité depuis la liste
if (isset($_POST['update_points'])) {
    $user_id = (int)$_POST['user_id'];
    $points = (int)$_POST['points'];
    $action = $_POST['action_points'];

    if ($points > 0) {
        if ($action === 'ajouter') {
            $sql = "UPDATE utilisateurs SET points_fidelite = points_fidelite + ? WHERE id = ?";
        } elseif ($action === 'retirer') {
            $sql = "UPDATE utilisateurs SET points_fidelite = GREATEST(0, points_fidelite - ?) WHERE id = ?";
        }
        
        if (isset($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$points, $user_id]);

            if ($action === 'ajouter') {
                checkAndGenerateFidelityReward($user_id);
            }
        }
    }
    header('Location: users.php');
    exit;
}


// Gérer les actions de suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id !== $_SESSION['user']['id']) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
    }
    header('Location: users.php');
    exit;
}

// --- RÉCUPÉRATION DES DONNÉES ---
$users = $pdo->query("SELECT * FROM utilisateurs ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les données d'un utilisateur si on est en mode édition
$user_a_modifier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT id, nom, email, is_admin FROM utilisateurs WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $user_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">
<link rel="stylesheet" href="/assets/css/users.css"> 

<main class="admin-container">
    <div class="admin-header">
        <h1>👥 Gestion des Utilisateurs</h1>
        <a href="dashboard.php" class="btn-back-dashboard">Retour au Dashboard</a>
    </div>

  <div class="admin-grid">
    <div class="table-container">
        <h3>Liste des utilisateurs</h3>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nom / Email</th>
              <th>Rôle</th>
              <th>Points de fidélité</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td>
                    <strong><?= htmlspecialchars($user['nom']) ?></strong><br>
                    <small><?= htmlspecialchars($user['email']) ?></small>
                </td>
                <td><?= $user['is_admin'] ? '👑 Admin' : '👤 Client' ?></td>
                <td class="points-cell">
                    <form method="POST" class="user-points-form">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <span class="points-display"><?= $user['points_fidelite'] ?></span>
                        <input type="number" name="points" min="1" placeholder="Pts" required>
                        <button type="submit" name="action_points" value="ajouter" class="btn-points add">+</button>
                        <button type="submit" name="action_points" value="retirer" class="btn-points remove">-</button>
                        <input type="hidden" name="update_points" value="1">
                    </form>
                </td>
                <td class="actions">
                  <a href="?edit=<?= $user['id'] ?>#form-section" title="Modifier">✏️</a>
                  <?php if ($_SESSION['user']['id'] != $user['id']): ?>
                    <a href="?action=delete&id=<?= $user['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?')" title="Supprimer">🗑️</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>

    <aside class="admin-form-container" id="form-section">
        <?php if ($user_a_modifier): ?>
            <h2>Modifier l'utilisateur</h2>
            <form method="POST" action="users.php" class="admin-form">
                <input type="hidden" name="id" value="<?= $user_a_modifier['id'] ?>">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user_a_modifier['nom']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_a_modifier['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Nouveau mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Laisser vide pour ne pas changer">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_admin" value="1" <?= $user_a_modifier['is_admin'] ? 'checked' : '' ?> <?= $user_a_modifier['id'] === $_SESSION['user']['id'] ? 'disabled' : '' ?>>
                        Statut administrateur
                    </label>
                    <?php if ($user_a_modifier['id'] === $_SESSION['user']['id']): ?>
                        <small>Vous ne pouvez pas retirer votre propre statut d'administrateur.</small>
                    <?php endif; ?>
                </div>
                <button type="submit" name="modifier_utilisateur" class="btn-submit">Enregistrer</button>
                <a href="users.php" class="btn-cancel">Annuler</a>
            </form>
        <?php else: ?>
            <h2>Gestion des utilisateurs</h2>
            <p>Sélectionnez un utilisateur dans la liste pour modifier ses informations.</p>
            <p>Vous pouvez aussi ajuster les points de fidélité directement dans le tableau.</p>
        <?php endif; ?>
    </aside>
  </div>
</main>

<?php include '../includes/footer.php'; ?>