<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$id_produit = filter_input(INPUT_POST, 'id_produit', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user']['id'];

if (!$id_produit) {
    echo json_encode(['success' => false, 'error' => 'invalid_product']);
    exit;
}

// Vérifier si le produit est déjà liké
$stmt = $pdo->prepare("SELECT id FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
$stmt->execute([$user_id, $id_produit]);
$is_liked = $stmt->fetch();

try {
    if ($is_liked) {
        // Le produit est déjà liké, on le retire des favoris
        $stmt_delete = $pdo->prepare("DELETE FROM favoris WHERE id = ?");
        $stmt_delete->execute([$is_liked['id']]);
        echo json_encode(['success' => true, 'action' => 'unliked']);
    } else {
        // Le produit n'est pas liké, on l'ajoute aux favoris
        $stmt_insert = $pdo->prepare("INSERT INTO favoris (id_utilisateur, id_produit) VALUES (?, ?)");
        $stmt_insert->execute([$user_id, $id_produit]);
        echo json_encode(['success' => true, 'action' => 'liked']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'database_error', 'message' => $e->getMessage()]);
}

exit;