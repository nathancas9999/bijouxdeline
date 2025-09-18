<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$panier_count = isset($_SESSION['panier']) ? array_sum(array_column($_SESSION['panier'], 'qte')) : 0;
$loggedIn = isset($_SESSION['user']);
$userName = $loggedIn && isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Bijoux de Line 🌸</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Edu+NSW+ACT+Cursive:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" href="/assets/images/favicon.png" type="image/png">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/header.css">
</head>
<body>
<header class="site-header">
  <div class="header-logo">
    <a href="/index.php">
      <img src="/assets/images/logoB.jpg" alt="Bijoux de Line" class="logo-img" />
      <span class="logo-text">Bijoux de Line</span>
    </a>
  </div>
  <div class="nav-links">
    <nav class="header-nav">
      <a href="/index.php">Accueil</a>
      <a href="/pages/boutique.php">Boutique</a>
      <a href="/pages/a-propos.php">À propos</a>
      <a href="/pages/cgv.php">CGV</a>
    </nav>
    <div class="header-actions">
      <?php if ($loggedIn): ?>
        <a href="/pages/favoris.php" class="btn-icon-header" title="Mes favoris"><i class="fas fa-heart"></i></a>
      <?php endif; ?>
      <a href="/pages/panier.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cart-count" style="display: <?= $panier_count > 0 ? 'inline-block' : 'none' ?>;"><?= $panier_count ?></span>
      </a>
      <?php if ($loggedIn && $userName): ?>
        <span class="welcome-text">Bonjour, <?= htmlspecialchars($userName) ?></span>
        <a href="/profil.php" class="btn-icon-header" title="Mon profil"><i class="fas fa-user-circle"></i></a>
        <a href="/auth/logout.php" class="btn-icon-header" title="Déconnexion"><i class="fas fa-sign-out-alt"></i></a>
      <?php else: ?>
        <a href="/auth/login.php" class="btn-header">Connexion</a>
        <a href="/auth/register.php" class="btn-header primary">S'inscrire</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="burger" id="burger">
    <div></div><div></div><div></div>
  </div>
</header>