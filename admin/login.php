<?php
session_start();
include '../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND is_admin = 1");
    $stmt->execute([$_POST['email']]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['mot_de_passe'], $admin['mot_de_passe'])) {
        $_SESSION['admin'] = $admin['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Identifiants invalides.";
    }
}
?>

<form method="POST">
  <h2>Connexion admin</h2>
  <?php if (isset($error)) echo "<p>$error</p>"; ?>
  <input type="email" name="email" required placeholder="Email admin">
  <input type="password" name="mot_de_passe" required placeholder="Mot de passe">
  <button type="submit">Se connecter</button>
</form>
