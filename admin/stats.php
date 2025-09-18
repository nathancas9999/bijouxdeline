<?php
session_start();
if (!isset($_SESSION['admin'])) die('Accès refusé');
include '../includes/db.php';
include '../includes/auth-check.php';

$revenuTotal = $pdo->query("SELECT SUM(total) AS revenu FROM commandes")->fetch()['revenu'];
$produitsVendus = $pdo->query("SELECT SUM(quantite) AS vendus FROM details_commandes")->fetch()['vendus'];
$topProduits = $pdo->query("
    SELECT p.nom, SUM(dc.quantite) as total
    FROM details_commandes dc
    JOIN produits p ON p.id = dc.id_produit
    GROUP BY dc.id_produit
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();
?>

<h1>Statistiques</h1>
<p><strong>Total Revenu : </strong> <?= $revenuTotal ?> €</p>
<p><strong>Produits vendus : </strong> <?= $produitsVendus ?></p>

<h3>Top 5 produits</h3>
<ul>
<?php foreach($topProduits as $p): ?>
    <li><?= $p['nom'] ?> - <?= $p['total'] ?> vendus</li>
<?php endforeach; ?>
</ul>
