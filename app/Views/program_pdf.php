<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Programme NutriRegime</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body { background: #fff; }
    .print-page { max-width: 900px; margin: 0 auto; padding: 32px; }
    @media print { .no-print { display: none; } .print-page { padding: 0; } }
  </style>
</head>
<body>
  <main class="print-page">
    <div class="no-print topbar-actions"><a class="btn btn-secondary btn-sm" href="/user">Retour</a><button class="btn btn-primary btn-sm" onclick="window.print()">Enregistrer en PDF</button></div>
    <h1>Programme NutriRegime</h1>
    <p class="muted"><?= esc($user['nom_complet']) ?> - IMC <?= esc(number_format((float) $user['imc'], 2)) ?> - <?= esc($objectives[$suggestions['objectif']] ?? $suggestions['objectif']) ?></p>

    <h2>Regimes suggeres</h2>
    <table>
      <thead><tr><th>Regime</th><th>Duree</th><th>Prix</th><th>Poids cible</th></tr></thead>
      <tbody>
        <?php foreach ($suggestions['regimes'] as $regime): ?>
          <tr><td><?= esc($regime['nom']) ?></td><td><?= (int) $regime['duree_valeur'] ?> <?= esc($regime['duree_unite']) ?></td><td><?= esc(number_format((float) $regime['prix_final'], 2)) ?></td><td><?= esc(number_format((float) $regime['poids_cible'], 2)) ?> kg</td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>Activites sportives</h2>
    <table>
      <thead><tr><th>Activite</th><th>Intensite</th><th>Frequence</th><th>Calories/h</th></tr></thead>
      <tbody>
        <?php foreach ($suggestions['activites'] as $activite): ?>
          <tr><td><?= esc($activite['nom']) ?></td><td><?= esc($activite['intensite']) ?></td><td><?= (int) $activite['frequence_semaine'] ?> / semaine</td><td><?= (int) $activite['calories_par_heure'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>
  <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
