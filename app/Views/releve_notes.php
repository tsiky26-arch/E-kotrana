<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SysInfo - Releve de notes</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="logo-icon"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 2 2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
      <div><div class="brand-name">SysInfo</div><div class="brand-sub">Etudiant</div></div>
    </div>
    <div class="sidebar-section">Navigation</div>
    <a href="/user" class="nav-item">Mes notes</a>
    <a href="/releve" class="nav-item active">Releve de notes</a>
    <div class="sidebar-bottom">
      <a href="/logout" class="user-row"><div class="avatar">US</div><div class="user-info"><div class="name"><?= esc(session()->get('userName')) ?></div><div class="role">Se deconnecter</div></div></a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div class="topbar-title">Releve de notes</div>
      <div class="topbar-actions"><a href="/logout" class="btn btn-secondary btn-sm">Deconnexion</a></div>
    </div>

    <div class="content">
      <?php if (! $student): ?>
        <div class="alert alert-info"><span>Aucun etudiant n'est encore enregistre.</span></div>
      <?php else: ?>
        <div class="page-header">
          <div>
            <h2><?= esc($student['nom'] . ' ' . $student['prenom']) ?></h2>
            <div class="breadcrumb">Accueil / <span>Releve S3 et S4</span></div>
          </div>
        </div>

        <div class="kpi-grid">
          <div class="kpi-card"><div class="kpi-label">Moyenne S3</div><div class="kpi-value"><?= esc(number_format($summaryS3['moyenne'], 2)) ?></div></div>
          <div class="kpi-card"><div class="kpi-label">Moyenne S4</div><div class="kpi-value"><?= esc(number_format($summaryS4['moyenne'], 2)) ?></div></div>
          <div class="kpi-card"><div class="kpi-label">Moyenne L2</div><div class="kpi-value"><?= esc(number_format($summary['moyenne'], 2)) ?></div></div>
          <div class="kpi-card"><div class="kpi-label">Credits compenses</div><div class="kpi-value"><?= (int) $summary['compensatedCredits'] ?> / <?= (int) $summary['totalCredits'] ?></div></div>
        </div>

        <div class="table-card section-gap">
          <div class="table-toolbar">
            <div>
              <strong>Notes retenues</strong>
              <div class="field-hint">La meilleure note par matiere est retenue pour le calcul.</div>
            </div>
          </div>
          <table>
            <thead><tr><th>Semestre</th><th>Matiere</th><th>Credits</th><th>Note</th><th>Resultat</th><th>Compense</th></tr></thead>
            <tbody>
              <?php foreach ($summary['effective'] as $note): ?>
                <tr>
                  <td><?= esc($note['semestre']) ?></td>
                  <td><?= esc($note['intitule']) ?><?= (int) $note['is_optional'] === 1 ? ' (optionnelle)' : '' ?></td>
                  <td><?= (int) $note['credits'] ?></td>
                  <td><?= esc(number_format((float) $note['note'], 2)) ?></td>
                  <td><span class="badge <?= (float) $note['note'] >= 10 ? 'badge-green' : 'badge-red' ?>"><?= esc($note['resultat']) ?></span></td>
                  <td><span class="badge <?= $note['compensated'] ? 'badge-green' : 'badge-gray' ?>"><?= $note['compensated'] ? 'Oui' : 'Non' ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
