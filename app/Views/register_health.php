<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Profil sante</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-shell wide">
    <section class="auth-panel">
      <div class="auth-brand"><div class="logo-icon">2</div><div><div class="brand-name">Informations de sante</div><div class="brand-sub">Etape 2 sur 2</div></div></div>
      <h1>Completer le profil</h1>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
      <form method="post" action="/inscription/sante" class="auth-form">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div><label class="field-label">Taille en cm</label><input id="taille_cm" type="number" name="taille_cm" min="100" max="250" step="0.01" value="<?= esc(old('taille_cm')) ?>" required></div>
          <div><label class="field-label">Poids en kg</label><input id="poids_kg" type="number" name="poids_kg" min="20" max="300" step="0.01" value="<?= esc(old('poids_kg')) ?>" required></div>
        </div>
        <div id="imc-preview" class="alert alert-info">Votre IMC sera calcule automatiquement.</div>
        <button class="btn btn-primary" type="submit">Terminer l'inscription</button>
      </form>
    </section>
  </main>
  <script>
    const taille = document.getElementById('taille_cm');
    const poids = document.getElementById('poids_kg');
    const preview = document.getElementById('imc-preview');
    async function updateImc() {
      if (!taille.value || !poids.value) return;
      const response = await fetch(`/ajax/imc?taille_cm=${encodeURIComponent(taille.value)}&poids_kg=${encodeURIComponent(poids.value)}`);
      const data = await response.json();
      preview.textContent = `IMC estime : ${data.imc} - ${data.categorie}`;
    }
    taille.addEventListener('input', updateImc);
    poids.addEventListener('input', updateImc);
  </script>
</body>
</html>
