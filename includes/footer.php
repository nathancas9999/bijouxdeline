<link rel="stylesheet" href="/assets/css/footer.css">
<footer class="site-footer">
  <div class="container footer-container">
    <div class="footer-col">
      <h4>À propos</h4>
      <p>Bijoux de Line 🌸 — des créations raffinées pour sublimer votre élégance avec amour et passion.</p>
    </div>
    <div class="footer-col">
      <h4>Liens utiles</h4>
      <ul>
        <li><a href="/pages/boutique.php">Boutique</a></li>
        <li><a href="/pages/a-propos.php">À propos</a></li>
        <li><a href="/pages/cgv.php">CGV</a></li>
        <li><a href="/pages/panier.php">Panier</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <p><i class="fas fa-envelope"></i> contact@bijouxdeline.fr</p>
      <div class="social-icons">
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-tiktok"></i></a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Bijoux de Line. Tous droits réservés.</p>
  </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function() {

  // --- LOGIQUE POUR LE MENU BURGER ---
  const burger = document.getElementById('burger');
  const nav = document.querySelector('.nav-links');
  if (burger && nav) {
    burger.addEventListener("click", () => {
      nav.classList.toggle("show");
      burger.classList.toggle("open");
    });
  }

  // --- LOGIQUE POUR L'AJOUT AU PANIER EN AJAX ---
  document.body.addEventListener('submit', function(event) {
    if (event.target.matches('.add-to-cart-form')) {
      event.preventDefault();
      
      const form = event.target;
      const formData = new FormData(form);
      const button = form.querySelector('button[type="submit"]');
      const originalButtonText = button.innerHTML;

      button.disabled = true;

      fetch('/actions/ajouter_panier.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          button.innerHTML = 'Ajouté ✔️';
          const cartCountElement = document.getElementById('cart-count');
          if (cartCountElement) {
            cartCountElement.innerText = data.panier_count;
            cartCountElement.style.display = data.panier_count > 0 ? 'inline-block' : 'none';
          }
        } else if (data.error === 'stock_insufficient') {
          button.innerHTML = 'Stock épuisé !';
          button.style.backgroundColor = '#c62828';
        } else {
          button.innerHTML = 'Erreur';
        }

        setTimeout(() => {
          button.innerHTML = originalButtonText;
          button.disabled = false;
          button.style.backgroundColor = '';
        }, 2000);
      })
      .catch(error => {
        console.error("Erreur AJAX:", error);
        button.innerHTML = originalButtonText;
        button.disabled = false;
        button.style.backgroundColor = '';
      });
    }
  });

  // --- LOGIQUE POUR LE BOUTON "J'AIME" EN AJAX ---
  document.body.addEventListener('submit', function(event) {
      if (event.target.matches('.like-form')) {
          event.preventDefault();

          const form = event.target;
          const formData = new FormData(form);
          const button = form.querySelector('.btn-like');
          const icon = button.querySelector('i');

          fetch('/actions/like_produit.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  if (data.action === 'liked') {
                      button.classList.add('liked');
                      icon.classList.remove('far');
                      icon.classList.add('fas');
                  } else {
                      button.classList.remove('liked');
                      icon.classList.remove('fas');
                      icon.classList.add('far');
                  }
              } else if (data.error === 'not_logged_in') {
                  window.location.href = '/auth/login.php';
              }
          })
          .catch(error => console.error('Erreur Fetch:', error));
      }
  });

  // --- LOGIQUE POUR LE BOUTON "VOIR PLUS" ---
  const descriptionWrapper = document.querySelector('.description-wrapper');
  if (descriptionWrapper) {
    const description = descriptionWrapper.querySelector('.description');
    
    if (description.scrollHeight > description.clientHeight) {
      const seeMoreBtn = document.createElement('button');
      seeMoreBtn.innerText = 'Voir plus...';
      seeMoreBtn.className = 'btn-see-more';
      descriptionWrapper.appendChild(seeMoreBtn);

      seeMoreBtn.addEventListener('click', function() {
        description.classList.toggle('expanded');
        this.innerText = description.classList.contains('expanded') ? 'Voir moins' : 'Voir plus...';
      });
    }
  }

});
</script>
</body>
</html>