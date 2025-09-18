<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header('Location: /index.php');
    exit;
}
include '../includes/db.php';
$revenu_30_jours = $pdo->query("SELECT SUM(total) FROM commandes WHERE date_commande >= DATE(NOW()) - INTERVAL 30 DAY")->fetchColumn();
$commandes_30_jours = $pdo->query("SELECT COUNT(*) FROM commandes WHERE date_commande >= DATE(NOW()) - INTERVAL 30 DAY")->fetchColumn();
$total_utilisateurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE is_admin = 0")->fetchColumn();
$meilleure_vente_stmt = $pdo->query("
    SELECT p.nom, SUM(dc.quantite) as total
    FROM details_commandes dc
    JOIN produits p ON p.id = dc.id_produit
    GROUP BY dc.id_produit
    ORDER BY total DESC
    LIMIT 1
");
$meilleure_vente = $meilleure_vente_stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.dashboard-container { max-width: 900px; margin: 120px auto 40px; padding: 0 20px; }
.dashboard-container h1, .dashboard-container > p { text-align: center; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem; margin-bottom: 2.5rem; padding: 2rem; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.stat-card { background: #fffafc; border-radius: 10px; padding: 1.5rem; text-align: center; border: 1px solid #fdeef4; }
.stat-card .icon { font-size: 2rem; color: #d46a92; margin-bottom: 0.75rem; }
.stat-card h4 { margin: 0 0 0.5rem; font-size: 1rem; color: #a75d67; font-weight: 600; }
.stat-card p { margin: 0; font-size: 1.8rem; font-weight: bold; color: #4c3a4c; line-height: 1.2; }
.nav-section { background: #ffffff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.nav-section h2 { font-family: 'Edu NSW ACT Cursive', cursive; color: #a75d67; font-size: 2.5rem; margin-top: 0; margin-bottom: 1.5rem; text-align: center; }
.nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; }
.nav-card { background: #fffafc; border-radius: 10px; padding: 1.5rem 1rem; text-align: center; text-decoration: none; color: #4c3a4c; border: 1px solid #fdeef4; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.nav-card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(212, 106, 146, 0.1); }
.nav-card i { font-size: 2rem; color: #d46a92; margin-bottom: 0.75rem; }
.nav-card h3 { font-family: 'Poppins', sans-serif; color: #a75d67; font-size: 1.1rem; font-weight: 600; margin: 0; }
</style>

<main class="dashboard-container">
  <h1>Dashboard Admin 🧑‍💼</h1>
  <p>Vue d'ensemble de l'activité de votre boutique sur les 30 derniers jours.</p>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="icon"><i class="fas fa-euro-sign"></i></div>
      <h4>Revenu</h4>
      <p><?= number_format($revenu_30_jours ?? 0, 2, ',', ' ') ?> €</p>
    </div>
    <div class="stat-card">
      <div class="icon"><i class="fas fa-shopping-cart"></i></div>
      <h4>Nouvelles Commandes</h4>
      <p><?= $commandes_30_jours ?? 0 ?></p>
    </div>
    <div class="stat-card">
      <div class="icon"><i class="fas fa-users"></i></div>
      <h4>Total Clients</h4>
      <p><?= $total_utilisateurs ?? 0 ?></p>
    </div>
    <div class="stat-card">
      <div class="icon"><i class="fas fa-star"></i></div>
      <h4>Meilleure Vente</h4>
      <p style="font-size: 1.2rem;"><?= $meilleure_vente ? htmlspecialchars($meilleure_vente['nom']) : 'N/A' ?></p>
    </div>
  </div>

  <div class="nav-section">
    <h2>Accès Rapide</h2>
    <div class="nav-grid">
      <a href="commandes.php" class="nav-card"><i class="fas fa-box-open"></i><h3>Commandes</h3></a>
      <a href="produits.php" class="nav-card"><i class="fas fa-gem"></i><h3>Produits</h3></a>
      <a href="temoignages.php" class="nav-card"><i class="fas fa-comments"></i><h3>Témoignages</h3></a>
      <a href="users.php" class="nav-card"><i class="fas fa-user-friends"></i><h3>Utilisateurs</h3></a>
      <a href="promos.php" class="nav-card"><i class="fas fa-ticket-alt"></i><h3>Codes Promo</h3></a>
    </div>
  </div>
</main>
<?php include '../includes/footer.php'; ?>