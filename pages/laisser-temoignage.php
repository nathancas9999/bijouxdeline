<?php
session_start();
include '../includes/db.php';

// Sécurité : si l'utilisateur n'est pas connecté, on le redirige.
if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="/assets/css/profil.css">
<link rel="stylesheet" href="/assets/css/boutique.css">


<main class="profil-container" style="max-width: 800px;">
    <div class="profil-card">
        <h1>✍️ Laissez votre avis sur Bijoux de Line</h1>
        <p>Votre opinion est précieuse ! Partagez votre expérience avec nous.</p>

        <form action="/actions/submit_temoignage.php" method="POST" enctype="multipart/form-data" class="profil-form" style="margin-top: 2rem;">
            <div class="form-group">
                <label for="note">Votre note</label>
                <select name="note" id="note" required style="padding: 12px; border-radius: 8px; border: 1px solid #ddd; width: 100%;">
                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                    <option value="4">⭐⭐⭐⭐ (Très bien)</option>
                    <option value="3">⭐⭐⭐ (Bien)</option>
                    <option value="2">⭐⭐ (Peut mieux faire)</option>
                    <option value="1">⭐ (Décevant)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="commentaire">Votre commentaire</label>
                <textarea name="commentaire" id="commentaire" rows="6" placeholder="Parlez-nous de votre expérience..." required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd; resize: vertical;"></textarea>
            </div>
            <div class="form-group">
                <label for="photos">Ajoutez une ou plusieurs photos (obligatoire)</label>
                <input type="file" name="photos[]" id="photos" multiple accept="image/jpeg, image/png, image/gif" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <small>Vous pouvez sélectionner plusieurs images (max 5 Mo par image).</small>
            </div>
            <button type="submit" name="submit_temoignage" class="btn-submit" style="width: 100%;">Envoyer mon avis</button>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>