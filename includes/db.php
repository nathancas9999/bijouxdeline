<?php
require_once __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

function getProduitById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function checkAndGenerateFidelityReward($userId) {
    global $pdo;
    $stmt_user = $pdo->prepare("SELECT nom, points_fidelite FROM utilisateurs WHERE id = ?");
    $stmt_user->execute([$userId]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['points_fidelite'] >= 10) {
        $nom_user_safe = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $user['nom']), 0, 6));
        $code_unique = "FID-" . $nom_user_safe . "-" . substr(strtoupper(md5(uniqid(rand(), true))), 0, 4);
        $stmt_promo = $pdo->prepare("INSERT INTO promotions (code, type, valeur, date_expiration, max_uses, id_utilisateur) VALUES (?, 'fixed', 10, ?, 1, ?)");
        $stmt_promo->execute([$code_unique, date('Y-m-d', strtotime('+1 year')), $userId]);
        $stmt_deduct = $pdo->prepare("UPDATE utilisateurs SET points_fidelite = points_fidelite - 10 WHERE id = ?");
        $stmt_deduct->execute([$userId]);
        return true;
    }
    return false;
}

function getTemoignagesApprouves() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM temoignages WHERE est_approuve = 1 ORDER BY date_creation DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- FONCTION D'ENVOI D'EMAIL COMPLÈTE ET CORRIGÉE ---
function sendOrderConfirmationEmail($userId, $orderId, $orderData, $pdo) {
    $stmt_user = $pdo->prepare("SELECT nom, email FROM utilisateurs WHERE id = ?");
    $stmt_user->execute([$userId]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if (!$user) return;

    $products_html = '';
    foreach ($orderData['details'] as $item) {
        $productTotal = $item['prix'] * $item['quantite'];
        $precommande_tag = $item['is_precommande'] ? ' <small style="color:#ff9800;">(Précommande)</small>' : '';
        $products_html .= '<tr><td style="padding:10px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['nom']) . ' (x' . $item['quantite'] . ')' . $precommande_tag . '</td><td style="padding:10px;border-bottom:1px solid #eee;text-align:right;">' . number_format($productTotal, 2, ',', ' ') . ' €</td></tr>';
    }

    $adresse_html = nl2br(htmlspecialchars(implode("\n", $orderData['adresse_livraison'])));

    $body = '
    <!DOCTYPE html><html><body style="font-family: Arial, sans-serif; color: #333;">
        <div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
            <h1 style="color: #a75d67; text-align: center;">Merci pour votre commande !</h1>
            <p>Bonjour ' . htmlspecialchars($user['nom']) . ',</p>
            <p>Votre commande n°<strong>' . $orderId . '</strong> a bien été enregistrée et est en cours de préparation.</p>
            <h2 style="color: #d46a92;">Récapitulatif de la commande</h2>
            <table style="width: 100%; border-collapse: collapse;">' . $products_html . '</table>
            <table style="width: 100%; margin-top: 20px;">
                <tr><td style="text-align: right;">Sous-total :</td><td style="text-align: right;">' . number_format($orderData['sous_total'], 2, ',', ' ') . ' €</td></tr>
                <tr><td style="text-align: right;">Frais de port :</td><td style="text-align: right;">' . ($orderData['frais_port'] > 0 ? number_format($orderData['frais_port'], 2, ',', ' ') . ' €' : 'Offerts') . '</td></tr>';
    if ($orderData['reduction'] > 0) {
        $body .= '<tr><td style="text-align: right;">Réduction (' . htmlspecialchars($orderData['code_promo']) . ') :</td><td style="text-align: right; color: green;">-' . number_format($orderData['reduction'], 2, ',', ' ') . ' €</td></tr>';
    }
    $body .= '<tr style="font-weight: bold; font-size: 1.2em;"><td style="text-align: right; padding-top: 10px; border-top: 1px solid #ccc;">Total :</td><td style="text-align: right; padding-top: 10px; border-top: 1px solid #ccc;">' . number_format($orderData['total_final'], 2, ',', ' ') . ' €</td></tr>
            </table>
            <h2 style="color: #d46a92; margin-top: 30px;">Adresse de livraison</h2>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">' . $adresse_html . '</div>
            <p style="text-align: center; margin-top: 30px;">Merci de votre confiance,<br>L\'équipe Bijoux de Line</p>
        </div>
    </body></html>';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = GMAIL_USER;
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;
        $mail->Password   = GMAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(GMAIL_USER, 'Bijoux de Line');
        $mail->addAddress($user['email'], $user['nom']);
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation de votre commande Bijoux de Line #' . $orderId;
        $mail->Body    = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("L'e-mail de confirmation n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}");
    }
}