<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Profil utilisateur</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="logo-icon">NR</div><div><div class="brand-name">NutriRegime</div><div class="brand-sub">Front Office</div></div></div>
    <div class="sidebar-section">Navigation</div>
    <a class="nav-item" href="/user">Tableau de bord</a>
    <a class="nav-item active" href="/user/profil">Mon profil</a>
    <a class="nav-item" href="/user/export">Export PDF</a>
    <a class="nav-item" href="/logout">Deconnexion</a>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="topbar-title">Mon profil</div>
      <div class="topbar-actions"><a class="btn btn-secondary btn-sm" href="/user">Retour dashboard</a></div>
    </div>

    <div class="content">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <section class="form-card">
        <div class="form-section-title">Informations personnelles</div>
        <form method="post" action="/user/profil" class="form-grid cols-3">
          <?= csrf_field() ?>
          <div><label class="field-label">Nom complet</label><input name="nom_complet" value="<?= esc($user['nom_complet']) ?>" required></div>
          <div><label class="field-label">Email</label><input value="<?= esc($user['email']) ?>" disabled></div>
          <div><label class="field-label">Date naissance</label><input type="date" name="date_naissance" value="<?= esc($user['date_naissance']) ?>" required></div>
          <div><label class="field-label">Genre</label>
            <select name="genre">
              <option value="homme" <?= $user['genre'] === 'homme' ? 'selected' : '' ?>>homme</option>
              <option value="femme" <?= $user['genre'] === 'femme' ? 'selected' : '' ?>>femme</option>
              <option value="autre" <?= $user['genre'] === 'autre' ? 'selected' : '' ?>>autre</option>
            </select>
          </div>
          <div><label class="field-label">Taille (cm)</label><input type="number" step="0.01" min="100" max="250" name="taille_cm" value="<?= esc($user['taille_cm']) ?>" required></div>
          <div><label class="field-label">Poids (kg)</label><input type="number" step="0.01" min="20" max="300" name="poids_kg" value="<?= esc($user['poids_kg']) ?>" required></div>
          <div><label class="field-label">Objectif</label>
            <select name="objectif">
              <option value="">Aucun</option>
              <option value="augmenter_poids" <?= $user['objectif'] === 'augmenter_poids' ? 'selected' : '' ?>>Augmenter son poids</option>
              <option value="reduire_poids" <?= $user['objectif'] === 'reduire_poids' ? 'selected' : '' ?>>Reduire son poids</option>
              <option value="imc_ideal" <?= $user['objectif'] === 'imc_ideal' ? 'selected' : '' ?>>Atteindre son IMC ideal</option>
            </select>
          </div>
          <div class="span-3"><button class="btn btn-primary" type="submit">Enregistrer le profil</button></div>
        </form>
      </section>

      <section class="table-card section-gap">
        <div class="table-toolbar"><strong>Historique du poids</strong></div>
        <table>
          <thead><tr><th>Date</th><th>Poids (kg)</th><th>IMC</th><th>Note</th></tr></thead>
          <tbody>
            <?php foreach ($history as $row): ?>
              <tr>
                <td><?= esc($row['enregistre_at']) ?></td>
                <td><?= esc(number_format((float) $row['poids_kg'], 2)) ?></td>
                <td><?= esc(number_format((float) $row['imc'], 2)) ?></td>
                <td><?= esc($row['note'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>
</div>
</body>
</html>
