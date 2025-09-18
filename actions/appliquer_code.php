<?php
session_start();
require_once('../includes/db.php');

unset($_SESSION['message_promo']);
unset($_SESSION['promo']);

if (isset($_POST['code_promo'])) {
    $code = trim($_POST['code_promo']);
    $user_id = $_SESSION['user']['id'] ?? 0;

    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND date_expiration >= CURDATE()");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promo) {
        $is_valid = true;
        if ($promo['max_uses'] > 0 && $promo['times_used'] >= $promo['max_uses']) {
            $is_valid = false;
            $_SESSION['message_promo'] = ['type' => 'error', 'text' => 'Ce code a atteint sa limite d\'utilisation.'];
        } elseif ($promo['id_utilisateur'] !== null && $promo['id_utilisateur'] != $user_id) {
            $is_valid = false;
            $_SESSION['message_promo'] = ['type' => 'error', 'text' => 'Ce code ne vous est pas attribué.'];
        }

        if ($is_valid) {
            $_SESSION['promo'] = ['code' => $promo['code'], 'type' => $promo['type'], 'valeur' => $promo['valeur']];
            $_SESSION['promo_utilise'] = $_SESSION['promo']; 
            $_SESSION['message_promo'] = ['type' => 'success', 'text' => 'Code promo appliqué !'];
        }

    } else {
        $_SESSION['message_promo'] = ['type' => 'error', 'text' => 'Ce code est invalide ou a expiré.'];
    }
}

header('Location: ../pages/panier.php');
exit;