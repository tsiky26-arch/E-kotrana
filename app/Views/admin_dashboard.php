<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriRegime - Back Office</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="logo-icon">BO</div><div><div class="brand-name">NutriRegime</div><div class="brand-sub">Back Office</div></div></div>
    <div class="sidebar-section">Navigation</div>
    <a class="nav-item active" href="/admin/dashboard">Tableau de bord</a>
    <a class="nav-item" href="#regimes">Regimes</a>
    <a class="nav-item" href="#activites">Activites</a>
    <a class="nav-item" href="#codes">Codes</a>
    <a class="nav-item" href="#parametres">Parametres</a>
    <div class="sidebar-bottom"><a href="/logout" class="user-row"><div class="avatar">AD</div><div class="user-info"><div class="name"><?= esc(session()->get('adminName')) ?></div><div class="role">Se deconnecter</div></div></a></div>
  </aside>

  <main class="main">
    <div class="topbar"><div class="topbar-title">Administration</div><div class="topbar-actions"><a href="/logout" class="btn btn-secondary btn-sm">Deconnexion</a></div></div>
    <div class="content">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <div class="page-header"><div><h2>Tableau de bord</h2><div class="breadcrumb">Statistiques, CRUD et validation des codes portefeuille</div></div></div>

      <section class="kpi-grid">
        <div class="kpi-card"><div class="kpi-label">Utilisateurs</div><div class="kpi-value"><?= (int) $stats['users'] ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Gold</div><div class="kpi-value"><?= (int) $stats['gold'] ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Programmes</div><div class="kpi-value"><?= (int) $stats['programmes'] ?></div></div>
        <div class="kpi-card"><div class="kpi-label">CA</div><div class="kpi-value"><?= esc(number_format((float) $stats['ca'], 2)) ?></div></div>
      </section>

      <section class="dash-grid section-gap">
        <div class="form-card">
          <div class="form-section-title">Graphe objectifs</div>
          <div class="bar-chart">
            <?php foreach ($stats['objectifs'] as $row): ?>
              <?php $width = max(8, min(100, (int) $row['total'] * 20)); ?>
              <div class="bar-row"><span><?= esc($objectives[$row['objectif']] ?? $row['objectif']) ?></span><div><i style="width: <?= $width ?>%"></i></div><strong><?= (int) $row['total'] ?></strong></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-card">
          <div class="form-section-title">Tableau croise genre / objectif</div>
          <table><thead><tr><th>Genre</th><th>Objectif</th><th>Total</th></tr></thead><tbody>
            <?php foreach ($stats['genre'] as $row): ?>
              <tr><td><?= esc($row['genre']) ?></td><td><?= esc($objectives[$row['objectif']] ?? $row['objectif']) ?></td><td><?= (int) $row['total'] ?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </section>

      <section id="regimes" class="form-card section-gap">
        <div class="form-section-title">CRUD des regimes</div>
        <form method="post" action="/admin/regimes" class="form-grid cols-3">
          <?= csrf_field() ?>
          <div><label class="field-label">Nom</label><input name="nom" required></div>
          <div><label class="field-label">Prix</label><input type="number" step="0.01" name="prix" required></div>
          <div><label class="field-label">Duree</label><div class="inline-actions"><input type="number" name="duree_valeur" value="4" min="1"><select name="duree_unite"><option value="semaines">semaines</option><option value="mois">mois</option></select></div></div>
          <div><label class="field-label">Variation min kg</label><input type="number" step="0.01" name="variation_poids_min_kg" value="1"></div>
          <div><label class="field-label">Variation max kg</label><input type="number" step="0.01" name="variation_poids_max_kg" value="3"></div>
          <div><label class="field-label">% viande</label><input type="number" step="0.01" name="pct_viande" value="25"></div>
          <div><label class="field-label">% poisson</label><input type="number" step="0.01" name="pct_poisson" value="25"></div>
          <div><label class="field-label">% volaille</label><input type="number" step="0.01" name="pct_volaille" value="25"></div>
          <div><label class="field-label">Objectifs</label><select name="objectif_cible[]" multiple><?php foreach ($objectives as $key => $label): ?><option value="<?= esc($key) ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
          <div class="span-3"><label class="field-label">Description</label><input name="description"></div>
          <button class="btn btn-primary" type="submit">Ajouter regime</button>
        </form>
        <div class="table-card inner">
          <table><thead><tr><th>Nom</th><th>Duree</th><th>Prix</th><th>Variation</th><th>Composition</th><th>Objectifs</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($regimes as $regime): ?>
              <tr>
                <form method="post" action="/admin/regimes/<?= (int) $regime['id'] ?>">
                  <?= csrf_field() ?>
                  <td>
                    <input name="nom" value="<?= esc($regime['nom']) ?>" required>
                    <div class="field-hint"><?= (int) $regime['actif'] === 1 ? 'Actif' : 'Desactive' ?></div>
                    <input type="hidden" name="description" value="<?= esc($regime['description']) ?>">
                  </td>
                  <td>
                    <div class="inline-actions">
                      <input type="number" min="1" name="duree_valeur" value="<?= esc($regime['duree_valeur']) ?>" required>
                      <select name="duree_unite">
                        <option value="semaines" <?= $regime['duree_unite'] === 'semaines' ? 'selected' : '' ?>>semaines</option>
                        <option value="mois" <?= $regime['duree_unite'] === 'mois' ? 'selected' : '' ?>>mois</option>
                      </select>
                    </div>
                  </td>
                  <td><input type="number" step="0.01" name="prix" value="<?= esc($regime['prix']) ?>" required></td>
                  <td>
                    <div class="inline-actions">
                      <input type="number" step="0.01" name="variation_poids_min_kg" value="<?= esc($regime['variation_poids_min_kg']) ?>">
                      <input type="number" step="0.01" name="variation_poids_max_kg" value="<?= esc($regime['variation_poids_max_kg']) ?>">
                    </div>
                  </td>
                  <td>
                    <div class="inline-actions">
                      <input type="number" step="0.01" name="pct_viande" value="<?= esc($regime['pct_viande']) ?>">
                      <input type="number" step="0.01" name="pct_poisson" value="<?= esc($regime['pct_poisson']) ?>">
                      <input type="number" step="0.01" name="pct_volaille" value="<?= esc($regime['pct_volaille']) ?>">
                    </div>
                  </td>
                  <td>
                    <?php $selectedObjectifs = array_map('trim', explode(',', (string) $regime['objectif_cible'])); ?>
                    <select name="objectif_cible[]" multiple>
                      <?php foreach ($objectives as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= in_array($key, $selectedObjectifs, true) ? 'selected' : '' ?>><?= esc($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <button class="btn btn-secondary btn-sm" type="submit">Modifier</button>
                    <button class="btn btn-danger btn-sm" type="submit" formaction="/admin/regimes/<?= (int) $regime['id'] ?>/delete">Desactiver</button>
                  </td>
                </form>
              </tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
        <div class="field-hint section-gap">Pour modifier un regime, reutilisez le formulaire d'ajout avec les nouvelles valeurs puis validez. Le bouton "Reenregistrer" confirme rapidement les donnees actuelles.</div>
      </section>

      <section id="activites" class="form-card section-gap">
        <div class="form-section-title">CRUD des activites sportives</div>
        <form method="post" action="/admin/activites" class="form-grid cols-3">
          <?= csrf_field() ?>
          <div><label class="field-label">Nom</label><input name="nom" required></div>
          <div><label class="field-label">Categorie</label><select name="categorie_id"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= esc($category['nom']) ?></option><?php endforeach; ?></select></div>
          <div><label class="field-label">Intensite</label><select name="intensite"><option value="faible">faible</option><option value="moderee">moderee</option><option value="elevee">elevee</option></select></div>
          <div><label class="field-label">Calories par heure</label><input type="number" name="calories_par_heure" value="250"></div>
          <div><label class="field-label">Frequence semaine</label><input type="number" name="frequence_semaine" value="3"></div>
          <div><label class="field-label">Objectifs</label><select name="objectif_compatible[]" multiple><?php foreach ($objectives as $key => $label): ?><option value="<?= esc($key) ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
          <div class="span-3"><label class="field-label">Description</label><input name="description"></div>
          <button class="btn btn-primary" type="submit">Ajouter activite</button>
        </form>
        <div class="table-card inner"><table><thead><tr><th>Nom</th><th>Categorie</th><th>Intensite</th><th>Calories</th><th>Frequence</th><th>Objectifs</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($activites as $activite): ?>
            <tr>
              <form method="post" action="/admin/activites/<?= (int) $activite['id'] ?>">
                <?= csrf_field() ?>
                <td>
                  <input name="nom" value="<?= esc($activite['nom']) ?>" required>
                  <input type="hidden" name="description" value="<?= esc($activite['description']) ?>">
                </td>
                <td>
                  <select name="categorie_id">
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === (int) $activite['categorie_id'] ? 'selected' : '' ?>><?= esc($category['nom']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <select name="intensite">
                    <option value="faible" <?= $activite['intensite'] === 'faible' ? 'selected' : '' ?>>faible</option>
                    <option value="moderee" <?= $activite['intensite'] === 'moderee' ? 'selected' : '' ?>>moderee</option>
                    <option value="elevee" <?= $activite['intensite'] === 'elevee' ? 'selected' : '' ?>>elevee</option>
                  </select>
                </td>
                <td><input type="number" name="calories_par_heure" value="<?= esc($activite['calories_par_heure']) ?>"></td>
                <td><input type="number" name="frequence_semaine" value="<?= esc($activite['frequence_semaine']) ?>"></td>
                <td>
                  <?php $selectedCompat = array_map('trim', explode(',', (string) $activite['objectif_compatible'])); ?>
                  <select name="objectif_compatible[]" multiple>
                    <?php foreach ($objectives as $key => $label): ?>
                      <option value="<?= esc($key) ?>" <?= in_array($key, $selectedCompat, true) ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <button class="btn btn-secondary btn-sm" type="submit">Modifier</button>
                  <button class="btn btn-danger btn-sm" type="submit" formaction="/admin/activites/<?= (int) $activite['id'] ?>/delete">Desactiver</button>
                </td>
              </form>
            </tr>
          <?php endforeach; ?>
        </tbody></table></div>
        <div class="field-hint section-gap">Pour modifier une activite, reutilisez le formulaire d'ajout avec les nouvelles valeurs puis validez. Le bouton "Reenregistrer" confirme rapidement les donnees actuelles.</div>
      </section>

      <section id="codes" class="form-card section-gap">
        <div class="form-section-title">Validation des codes portefeuille</div>
        <form method="post" action="/admin/codes" class="form-grid cols-3">
          <?= csrf_field() ?>
          <div><label class="field-label">Code</label><input name="code" required></div>
          <div><label class="field-label">Valeur</label><input type="number" step="0.01" name="valeur" required></div>
          <div><label class="field-label">Utilisations max</label><input type="number" name="utilisations_max" value="1"></div>
          <div><label class="field-label">Expire le</label><input type="datetime-local" name="expire_at"></div>
          <button class="btn btn-primary" type="submit">Creer code</button>
        </form>
        <div class="table-card inner"><table><thead><tr><th>Code</th><th>Valeur</th><th>Utilisations</th><th>Expire</th><th>Statut</th></tr></thead><tbody>
          <?php foreach ($codes as $code): ?>
            <tr><td><strong><?= esc($code['code']) ?></strong></td><td><?= esc(number_format((float) $code['valeur'], 2)) ?></td><td><?= (int) $code['utilisations_count'] ?> / <?= (int) $code['utilisations_max'] ?></td><td><?= esc($code['expire_at'] ?? '-') ?></td><td><?= (int) $code['actif'] === 1 ? 'Actif' : 'Inactif' ?></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      </section>

      <section id="parametres" class="form-card section-gap">
        <div class="form-section-title">CRUD des parametres necessaires</div>
        <form method="post" action="/admin/parametres">
          <?= csrf_field() ?>
          <div class="form-grid">
            <?php foreach ($parametres as $param): ?>
              <div><label class="field-label"><?= esc($param['cle']) ?></label><input name="parametres[<?= (int) $param['id'] ?>]" value="<?= esc($param['valeur']) ?>"><div class="field-hint"><?= esc($param['description']) ?></div></div>
            <?php endforeach; ?>
          </div>
          <button class="btn btn-primary" type="submit">Enregistrer les parametres</button>
        </form>
      </section>
    </div>
  </main>
</div>
</body>
</html>
