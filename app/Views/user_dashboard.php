<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Espace utilisateur</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="logo-icon">NR</div><div><div class="brand-name">NutriRegime</div><div class="brand-sub">Front Office</div></div></div>
    <div class="sidebar-section">Navigation</div>
    <a class="nav-item active" href="/user">Tableau de bord</a>
    <a class="nav-item" href="/user/export">Export PDF</a>
    <a class="nav-item" href="/logout">Deconnexion</a>
    <div class="sidebar-bottom"><div class="user-row"><div class="avatar"><?= esc(strtoupper(substr($user['nom_complet'], 0, 2))) ?></div><div class="user-info"><div class="name"><?= esc($user['nom_complet']) ?></div><div class="role"><?= (int) $user['is_gold'] === 1 ? 'Gold actif' : 'Standard' ?></div></div></div></div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="topbar-title">Programme sante</div>
      <div class="topbar-actions"><a class="btn btn-secondary btn-sm" href="/user/export">Exporter PDF</a></div>
    </div>
    <div class="content">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <div class="page-header">
        <div>
          <h2>Bonjour <?= esc($user['nom_complet']) ?></h2>
          <div class="breadcrumb">IMC <?= esc(number_format((float) $user['imc'], 2)) ?> / Objectif courant : <?= esc($objectives[$suggestions['objectif']] ?? 'A choisir') ?></div>
        </div>
      </div>

      <section class="kpi-grid">
        <div class="kpi-card"><div class="kpi-label">Taille</div><div class="kpi-value"><?= esc($user['taille_cm']) ?> cm</div></div>
        <div class="kpi-card"><div class="kpi-label">Poids</div><div class="kpi-value"><?= esc($user['poids_kg']) ?> kg</div></div>
        <div class="kpi-card"><div class="kpi-label">Solde</div><div class="kpi-value"><?= esc(number_format((float) $user['solde'], 2)) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Remise Gold</div><div class="kpi-value"><?= (int) $user['is_gold'] === 1 ? esc($goldDiscount) . '%' : '0%' ?></div></div>
      </section>

      <section class="visual-strip section-gap">
        <article class="visual-card">
          <img src="https://images.unsplash.com/photo-1749280446532-60869b4b9863?auto=format&fit=crop&q=80&w=900" alt="Bol equilibre avec legumes" loading="lazy">
          <div><strong>Repas equilibres</strong><span>Des assiettes colorees pour tenir le rythme.</span></div>
        </article>
        <article class="visual-card">
          <img src="https://images.unsplash.com/photo-1728636945265-03f10f048728?auto=format&fit=crop&q=80&w=900" alt="Salade de legumes frais" loading="lazy">
          <div><strong>Suivi nutrition</strong><span>Des choix simples, visibles et mesurables.</span></div>
        </article>
        <article class="visual-card">
          <img src="https://images.unsplash.com/photo-1773816709679-d335f43aba83?auto=format&fit=crop&q=80&w=900" alt="Marche active dans un parc" loading="lazy">
          <div><strong>Activite douce</strong><span>Un programme qui avance avec votre quotidien.</span></div>
        </article>
      </section>

      <section class="dash-grid section-gap">
        <form class="form-card" method="post" action="/user/objectif">
          <?= csrf_field() ?>
          <div class="form-section-title">Choisir un objectif</div>
          <div class="choice-grid">
            <?php foreach ($objectives as $key => $label): ?>
              <label class="choice-card <?= $suggestions['objectif'] === $key ? 'selected' : '' ?>">
                <input type="radio" name="objectif" value="<?= esc($key) ?>" <?= $suggestions['objectif'] === $key ? 'checked' : '' ?>>
                <strong><?= esc($label) ?></strong>
              </label>
            <?php endforeach; ?>
          </div>
          <button class="btn btn-primary" type="submit">Generer mes suggestions</button>
        </form>

        <div class="form-card">
          <div class="form-section-title">Porte monnaie et Gold</div>
          <form class="inline-actions" method="post" action="/user/portefeuille">
            <?= csrf_field() ?>
            <input name="code" placeholder="Code ex: WELCOME50" required>
            <button class="btn btn-secondary" type="submit">Crediter</button>
          </form>
          <form class="section-gap" method="post" action="/user/gold">
            <?= csrf_field() ?>
            <p class="muted">Option Gold : paiement unique de <?= esc($goldPrice) ?> avec <?= esc($goldDiscount) ?>% de remise sur tous les regimes.</p>
            <button class="btn btn-success" type="submit" <?= (int) $user['is_gold'] === 1 ? 'disabled' : '' ?>>Activer Gold</button>
          </form>
        </div>
      </section>

      <section class="table-card section-gap">
        <div class="table-toolbar"><div><strong>Regimes suggeres</strong><div class="field-hint">Prix ajuste automatiquement si Gold est actif.</div></div></div>
        <table>
          <thead><tr><th>Regime</th><th>Duree</th><th>Variation</th><th>Prix</th><th>Poids cible</th><th>Composition</th></tr></thead>
          <tbody>
            <?php foreach ($suggestions['regimes'] as $regime): ?>
              <tr>
                <td><strong><?= esc($regime['nom']) ?></strong><div class="field-hint"><?= esc($regime['description']) ?></div></td>
                <td><?= (int) $regime['duree_valeur'] ?> <?= esc($regime['duree_unite']) ?></td>
                <td><?= esc($regime['variation_poids_min_kg']) ?> a <?= esc($regime['variation_poids_max_kg']) ?> kg</td>
                <td><span class="badge badge-green"><?= esc(number_format((float) $regime['prix_final'], 2)) ?></span></td>
                <td><?= esc(number_format((float) $regime['poids_cible'], 2)) ?> kg</td>
                <td>V <?= esc($regime['pct_viande']) ?>% / P <?= esc($regime['pct_poisson']) ?>% / Vo <?= esc($regime['pct_volaille']) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="table-card section-gap">
        <div class="table-toolbar"><strong>Activites sportives necessaires</strong></div>
        <table>
          <thead><tr><th>Activite</th><th>Categorie</th><th>Intensite</th><th>Calories/h</th><th>Frequence</th></tr></thead>
          <tbody>
            <?php foreach ($suggestions['activites'] as $activite): ?>
              <tr><td><strong><?= esc($activite['nom']) ?></strong><div class="field-hint"><?= esc($activite['description']) ?></div></td><td><?= esc($activite['categorie']) ?></td><td><?= esc($activite['intensite']) ?></td><td><?= (int) $activite['calories_par_heure'] ?></td><td><?= (int) $activite['frequence_semaine'] ?> / semaine</td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="table-card section-gap">
        <div class="table-toolbar"><strong>Dernieres transactions</strong></div>
        <table>
          <thead><tr><th>Type</th><th>Montant</th><th>Solde apres</th><th>Motif</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($transactions as $tx): ?>
              <tr><td><?= esc($tx['type']) ?></td><td><?= esc(number_format((float) $tx['montant'], 2)) ?></td><td><?= esc(number_format((float) $tx['solde_apres'], 2)) ?></td><td><?= esc($tx['motif']) ?></td><td><?= esc($tx['created_at']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>
</div>
</body>
</html>
