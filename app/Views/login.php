<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Connexion</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-panel">
      <div class="auth-brand">
        <div class="logo-icon">NR</div>
        <div>
          <div class="brand-name">NutriRegime</div>
          <div class="brand-sub"><?= ($mode ?? 'user') === 'admin' ? 'Back Office' : 'Front Office' ?></div>
        </div>
      </div>

      <h1><?= ($mode ?? 'user') === 'admin' ? 'Connexion admin' : 'Connexion utilisateur' ?></h1>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= ($mode ?? 'user') === 'admin' ? site_url('admin/login') : site_url('login') ?>" class="auth-form">
        <?= csrf_field() ?>
        <div>
          <label class="field-label" for="email">E-mail</label>
          <input id="email" type="email" name="email" value="<?= esc(old('email') ?? (($mode ?? 'user') === 'admin' ? 'admin@app.com' : '')) ?>" required>
        </div>
        <div>
          <label class="field-label" for="password">Mot de passe</label>
          <input id="password" type="password" name="password" value="<?= ($mode ?? 'user') === 'admin' ? 'Admin1234!' : '' ?>" required>
        </div>
        <button class="btn btn-primary" type="submit">Se connecter</button>
      </form>

      <div class="auth-links">
        <?php if (($mode ?? 'user') === 'admin'): ?>
          <a href="<?= site_url('login') ?>">Connexion utilisateur</a>
        <?php else: ?>
          <a href="<?= site_url('inscription') ?>">Creer un compte</a>
          <a href="<?= site_url('admin') ?>">Back Office</a>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
