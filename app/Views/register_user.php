<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Inscription</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-shell wide">
    <section class="auth-panel">
      <div class="auth-brand"><div class="logo-icon">1</div><div><div class="brand-name">Informations utilisateur</div><div class="brand-sub">Etape 1 sur 2</div></div></div>
      <h1>Creer votre compte</h1>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
      <form method="post" action="/inscription" class="auth-form">
        <?= csrf_field() ?>
        <div><label class="field-label">Nom complet</label><input name="nom_complet" value="<?= esc(old('nom_complet')) ?>" required></div>
        <div><label class="field-label">E-mail</label><input type="email" name="email" value="<?= esc(old('email')) ?>" required></div>
        <div><label class="field-label">Mot de passe</label><input type="password" name="mot_de_passe" minlength="6" required></div>
        <div class="form-grid">
          <div><label class="field-label">Genre</label><select name="genre" required><option value="homme">Homme</option><option value="femme">Femme</option><option value="autre">Autre</option></select></div>
          <div><label class="field-label">Date de naissance</label><input type="date" name="date_naissance" value="<?= esc(old('date_naissance')) ?>" required></div>
        </div>
        <button class="btn btn-primary" type="submit">Continuer</button>
      </form>
      <div class="auth-links"><a href="/login">J'ai deja un compte</a></div>
    </section>
  </main>
</body>
</html>
