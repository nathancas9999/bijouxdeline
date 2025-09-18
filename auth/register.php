<?php
include '../includes/db.php';
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $mdp = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$nom, $email, $mdp]);
        $_SESSION['user'] = [
            'id' => $pdo->lastInsertId(),
            'nom' => $nom,
            'email' => $email,
            'is_admin' => 0
        ];
        header('Location: /index.php');
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error = "Cette adresse e-mail est déjà utilisée.";
        } else {
            $error = "Une erreur est survenue lors de l'inscription.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/auth.css">

<main class="auth-container">
    <div class="auth-card">
        <h1>Créer un compte</h1>

        <?php if ($error): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="nom">Votre prénom</label>
                <input type="text" id="nom" name="nom" required>
            </div>
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="auth-button">S'inscrire</button>
        </form>

        <p class="auth-switch-link">
            Déjà un compte ? <a href="login.php">Connectez-vous</a>
        </p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>