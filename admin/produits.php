<?php
session_start();
include '../includes/auth-check.php';
include '../includes/db.php';

// --- LOGIQUE DE GESTION ---

$message = null; // Pour les notifications

// Gérer le téléversement d'une nouvelle image
if (isset($_POST['upload_image'])) {
    if (isset($_FILES['nouvelle_image']) && $_FILES['nouvelle_image']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/';
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_name = basename($_FILES['nouvelle_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = uniqid('', true) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['nouvelle_image']['tmp_name'], $upload_dir . $new_file_name)) {
                $message = ['type' => 'success', 'text' => 'Image téléversée avec succès ! Son nom est : <strong>' . $new_file_name . '</strong>'];
            } else { $message = ['type' => 'error', 'text' => 'Erreur lors du déplacement de l\'image.']; }
        } else { $message = ['type' => 'error', 'text' => 'Type de fichier non autorisé.']; }
    } else { $message = ['type' => 'error', 'text' => 'Aucun fichier sélectionné ou erreur.'];}
}

// AJOUT D'UN PRODUIT
if (isset($_POST['ajouter'])) {
    $stmt = $pdo->prepare("INSERT INTO produits (nom, description, prix, poids, image, stock, categorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['nom'], $_POST['description'], $_POST['prix'], $_POST['poids'], $_POST['image'], $_POST['stock'], $_POST['categorie']]);
    header('Location: produits.php?add_success=1'); exit;
}

// SUPPRESSION D'UN PRODUIT
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
    $stmt->execute([$_GET['supprimer']]);
    header('Location: produits.php?delete_success=1'); exit;
}

// MISE À JOUR D'UN PRODUIT
if (isset($_POST['modifier'])) {
    $stmt = $pdo->prepare("UPDATE produits SET nom = ?, description = ?, prix = ?, poids = ?, image = ?, stock = ?, categorie = ? WHERE id = ?");
    $stmt->execute([$_POST['nom'], $_POST['description'], $_POST['prix'], $_POST['poids'], $_POST['image'], $_POST['stock'], $_POST['categorie'], $_POST['id']]);
    header('Location: produits.php?update_success=1'); exit;
}

// --- RÉCUPÉRATION DES DONNÉES ET FILTRAGE ---
$search_term = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$stock_filter = $_GET['stock'] ?? '';
$sql = "SELECT * FROM produits WHERE 1=1";
$params = [];
if (!empty($search_term)) { $sql .= " AND (nom LIKE ? OR description LIKE ?)"; $params[] = "%$search_term%"; $params[] = "%$search_term%"; }
if (!empty($categorie_filter)) { $sql .= " AND categorie = ?"; $params[] = $categorie_filter; }
if ($stock_filter === 'in_stock') { $sql .= " AND stock > 5";
} elseif ($stock_filter === 'low_stock') { $sql .= " AND stock > 0 AND stock <= 5";
} elseif ($stock_filter === 'out_of_stock') { $sql .= " AND stock = 0"; }
$sql .= " ORDER BY id DESC";
$stmt_produits = $pdo->prepare($sql);
$stmt_produits->execute($params);
$produits = $stmt_produits->fetchAll();
$categories = $pdo->query("SELECT DISTINCT categorie FROM produits ORDER BY categorie ASC")->fetchAll(PDO::FETCH_COLUMN);
$images = array_diff(scandir('../uploads/'), ['.', '..']);
$produit_a_modifier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $produit_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin.css">
<link rel="stylesheet" href="/assets/css/produits.css">

<main class="admin-container">
    <div class="admin-header">
        <h1>📦 Gestion des produits</h1>
        <a href="dashboard.php" class="btn-back-dashboard">Retour au Dashboard</a>
    </div>

    <?php if ($message): ?>
    <div class="message-banner <?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <div class="admin-grid">
        <div class="table-container">
            <h3>Catalogue Actuel</h3>
            <form method="GET" action="produits.php" class="search-form product-filters">
                <input type="text" name="search" placeholder="Rechercher par nom..." value="<?= htmlspecialchars($search_term) ?>">
                <select name="categorie">
                    <option value="">Toutes les catégories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $categorie_filter == $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="stock">
                    <option value="">Tous les stocks</option>
                    <option value="in_stock" <?= $stock_filter == 'in_stock' ? 'selected' : '' ?>>En stock</option>
                    <option value="low_stock" <?= $stock_filter == 'low_stock' ? 'selected' : '' ?>>Stock faible</option>
                    <option value="out_of_stock" <?= $stock_filter == 'out_of_stock' ? 'selected' : '' ?>>Épuisé</option>
                </select>
                <button type="submit">Filtrer</button>
                <?php if (!empty($search_term) || !empty($categorie_filter) || !empty($stock_filter)): ?>
                <a href="produits.php" class="btn-cancel-search">Annuler</a>
                <?php endif; ?>
            </form>

            <table class="admin-table products-table">
                <thead>
                    <tr><th>Image</th><th>Nom</th><th>Prix</th><th>Poids (g)</th><th>Stock</th><th>Catégorie</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($produits as $p): ?>
                    <tr>
                        <td class="product-thumb-cell"><img src="/uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>"></td>
                        <td><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                        <td><?= htmlspecialchars($p['poids']) ?>g</td>
                        <td class="stock-cell" style="color: <?= $p['stock'] > 5 ? 'green' : ($p['stock'] > 0 ? 'orange' : 'red') ?>;"><?= htmlspecialchars($p['stock']) ?></td>
                        <td><?= htmlspecialchars($p['categorie']) ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $p['id'] ?>#form-section" title="Modifier">✏️</a>
                            <a href="?supprimer=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce produit ?')" title="Supprimer">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <aside class="admin-form-container" id="form-section">
            <?php if ($produit_a_modifier): ?>
            <h2>✏️ Modifier un produit</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="id" value="<?= $produit_a_modifier['id'] ?>">
                <div class="form-group"><label>Nom</label><input name="nom" value="<?= htmlspecialchars($produit_a_modifier['nom']) ?>" required></div>
                <div class="form-group"><label>Description</label><textarea name="description"><?= htmlspecialchars($produit_a_modifier['description']) ?></textarea></div>
                <div class="form-group"><label>Prix (€)</label><input name="prix" type="number" step="0.01" value="<?= $produit_a_modifier['prix'] ?>" required></div>
                <div class="form-group"><label>Poids (grammes)</label><input name="poids" type="number" step="0.01" value="<?= $produit_a_modifier['poids'] ?>" required></div>
                <div class="form-group"><label>Stock</label><input name="stock" type="number" value="<?= $produit_a_modifier['stock'] ?>" required></div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="categorie" required>
                        <option value="">-- Choisir --</option>
                        <?php
                        $categories_all = ['Bagues', 'Bracelets', 'Colliers', 'Boucles d’oreilles', 'Piercings', 'Anklets', 'Autres'];
                        foreach ($categories_all as $cat): ?>
                        <option value="<?= $cat ?>" <?= $produit_a_modifier['categorie'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Image (nom du fichier)</label><input name="image" id="imageInput" value="<?= htmlspecialchars($produit_a_modifier['image']) ?>" required></div>
                <button type="submit" name="modifier" class="btn-submit">Enregistrer</button>
                <a href="produits.php" class="btn-cancel">Annuler</a>
            </form>
            <?php else: ?>
            <h2>➕ Ajouter un produit</h2>
            <div class="upload-section card-section">
                <h4>Téléverser une nouvelle image</h4>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="file" name="nouvelle_image" class="file-input" required>
                    <button type="submit" name="upload_image" class="btn-upload">Envoyer</button>
                </form>
            </div>
            <div class="image-gallery-section card-section">
                <h4>Sélectionner une image</h4>
                <div class="image-gallery">
                    <?php foreach (array_reverse($images) as $img): if(!is_dir('../uploads/'.$img)): ?>
                    <div class="thumb" onclick="setImage('<?= $img ?>')"><img src="/uploads/<?= $img ?>" alt="<?= $img ?>"><span><?= $img ?></span></div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <div class="add-product-form-section card-section">
                <h4>Informations du produit</h4>
                <form method="POST" class="admin-form">
                    <div class="form-group"><label>Nom</label><input name="nom" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                    <div class="form-group"><label>Prix (€)</label><input name="prix" type="number" step="0.01" required></div>
                    <div class="form-group"><label>Poids (grammes)</label><input name="poids" type="number" step="0.01" placeholder="Ex: 25.5" required></div>
                    <div class="form-group"><label>Stock</label><input name="stock" type="number" required></div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="categorie" required>
                            <option value="">-- Choisir --</option>
                            <?php $categories_all_add = ['Bagues', 'Bracelets', 'Colliers', 'Boucles d’oreilles', 'Piercings', 'Anklets', 'Autres'];
                            foreach ($categories_all_add as $cat): ?><option value="<?= $cat ?>"><?= $cat ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Image</label><input name="image" id="imageInput" placeholder="Cliquez sur une image ci-dessus" required readonly></div>
                    <button type="submit" name="ajouter" class="btn-submit">Ajouter</button>
                </form>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</main>
<script>
function setImage(filename) {
    const input = document.getElementById('imageInput');
    if (input) {
        input.value = filename;
        const currentSelected = document.querySelector('.thumb.selected');
        if (currentSelected) currentSelected.classList.remove('selected');
        event.currentTarget.classList.add('selected');
    }
}
</script>
<?php include '../includes/footer.php'; ?>