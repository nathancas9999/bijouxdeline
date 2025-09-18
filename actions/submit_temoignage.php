<?php
session_start();
include '../includes/db.php';

// 1. Sécurité et validations de base
if (!isset($_POST['submit_temoignage'])) {
    header('Location: /index.php');
    exit;
}

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

// Vérification que des photos ont été envoyées (obligatoire)
if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    header('Location: /pages/laisser-temoignage.php?error=nophoto');
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['nom'];
$note = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
$commentaire = trim(filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_SPECIAL_CHARS));

if (!$note || empty($commentaire)) {
    header('Location: /pages/laisser-temoignage.php?error=invaliddata');
    exit;
}

// 2. Gestion de l'upload des photos
$photo_names = [];
$upload_dir = __DIR__ . '/../uploads/temoignages/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$max_size = 5 * 1024 * 1024; // 5 Mo

foreach ($_FILES['photos']['name'] as $key => $name) {
    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photos']['tmp_name'][$key];
        $file_size = $_FILES['photos']['size'][$key];
        $file_ext_parts = explode('.', $name);
        $file_ext = strtolower(end($file_ext_parts));

        if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_size) {
            $new_filename = uniqid('temoignage_' . $user_id . '_', true) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $photo_names[] = $new_filename;
            }
        }
    }
}

// Si après le traitement, aucune photo valide n'a été uploadée
if (empty($photo_names)) {
    header('Location: /pages/laisser-temoignage.php?error=photofailed');
    exit;
}


// 3. Enregistrement en base de données avec approbation par défaut
$photos_json = json_encode($photo_names);

try {
    $stmt = $pdo->prepare(
        // LA MODIFICATION EST ICI : est_approuve est maintenant à 1
        "INSERT INTO temoignages (nom_client, commentaire, note, photos, est_approuve, date_creation) VALUES (?, ?, ?, ?, 1, NOW())"
    );
    $stmt->execute([$user_name, $commentaire, $note, $photos_json]);

    header('Location: /profil.php?testimonial_success=1');
    exit;

} catch (PDOException $e) {
    die("Erreur lors de l'enregistrement : " . $e->getMessage());
}
?>