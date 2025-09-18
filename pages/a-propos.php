<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>À propos - Bijoux de Line</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- STYLES GLOBAUX -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/header.css">
  <link rel="stylesheet" href="/assets/css/footer.css">

  <!-- PAGE STYLE -->
  <style>
    .about-section {
      padding: 100px 20px 60px;
      text-align: center;
      background: #fffafc;
    }

    .about-section h1 {
      font-family: 'Edu NSW ACT Cursive', cursive;
      font-size: 42px;
      color: #a75d67;
      margin-bottom: 20px;
    }

    .about-section p {
      max-width: 800px;
      margin: auto;
      font-size: 18px;
      line-height: 1.8;
      color: #4c3a4c;
    }

    .map-section {
      margin: 60px auto;
      max-width: 900px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .values-section {
      background: #fff0f5;
      padding: 60px 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem;
    }

    .value-card {
      background: white;
      border-radius: 10px;
      padding: 30px;
      width: 260px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.06);
      text-align: center;
    }

    .value-card h3 {
      color: #d46a92;
      margin-bottom: 10px;
    }

    .value-card p {
      font-size: 15px;
      color: #4c3a4c;
    }

    @media (max-width: 768px) {
      .about-section h1 {
        font-size: 32px;
      }

      .value-card {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<?php include('../includes/header.php'); ?>

<main>

  <section class="about-section">
    <h1>✨ À propos de Bijoux de Line ✨</h1>
    <p>
      Chez Bijoux de Line, chaque création raconte une histoire.  
      Née d’une passion pour l’art, l’élégance et la féminité, notre marque sublime les femmes avec des bijoux faits avec le cœur.
      Chaque pièce est pensée pour révéler la beauté unique de celles qui la portent. 💖
    </p>
  </section>

  <section class="map-section">
    <!-- Carte Google Maps -->
    <iframe
      src="https://www.google.com/maps?q=15+Rue+de+Rib%C3%A9court,+Paris,+France&output=embed"
      width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"></iframe>
  </section>

  <section class="values-section">
    <div class="value-card">
      <h3>🎨 Créativité</h3>
      <p>Chaque bijou est une œuvre d’art, conçue avec soin, style et émotion.</p>
    </div>
    <div class="value-card">
      <h3>💎 Qualité</h3>
      <p>Nous sélectionnons des matériaux durables et nobles pour des créations qui durent.</p>
    </div>
    <div class="value-card">
      <h3>📦 Livraison douce</h3>
      <p>Livraison rapide, éthique et joliment emballée. C’est un petit cadeau à soi-même.</p>
    </div>
  </section>

</main>

<?php include('../includes/footer.php'); ?>

</body>
</html>
