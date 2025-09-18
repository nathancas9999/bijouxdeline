<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

// Récupérer l'adresse de l'utilisateur depuis la BDD
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$user = $stmt->fetch();

// Si le formulaire est soumis, on enregistre l'adresse en session et on va au paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['adresse_livraison'] = [
        'adresse' => $_POST['adresse'],
        'code_postal' => $_POST['code_postal'],
        'ville' => $_POST['ville'],
        'pays' => $_POST['pays']
    ];
    header('Location: /pages/paiement.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/profil.css">

<main class="container" style="padding-top: 120px; max-width: 800px;">
    <h1>Adresse de Livraison</h1>
    <p>Veuillez confirmer votre adresse pour la livraison de votre commande.</p>

    <div class="profil-card">
        <form method="POST" class="profil-form">
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" required>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label for="code_postal">Code Postal</label>
                    <input type="text" name="code_postal" value="<?= htmlspecialchars($user['code_postal'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="flex: 2;">
                    <label for="ville">Ville</label>
                    <input type="text" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="pays">Pays</label>
                <input type="text" name="pays" value="<?= htmlspecialchars($user['pays'] ?? '') ?>" required>
            </div>
            <button type="submit">Continuer vers le paiement</button>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>