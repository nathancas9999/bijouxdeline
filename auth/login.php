<?php
include '../includes/db.php';
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['mot_de_passe'], $user['mot_de_passe'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nom' => $user['nom'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin'] ?? 0
        ];
        header('Location: /index.php');
        exit;
    } else {
        $error = "L'adresse e-mail ou le mot de passe est incorrect.";
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/auth.css">

<main class="auth-container">
    <div class="auth-card">
        <h1>Connexion</h1>
        
        <?php if ($error): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="auth-button">Se connecter</button>
        </form>

        <p class="auth-switch-link">
            Pas encore de compte ? <a href="register.php">Inscrivez-vous</a>
        </p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>