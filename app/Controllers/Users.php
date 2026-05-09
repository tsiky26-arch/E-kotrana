<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Users extends BaseController
{
    private array $objectiveLabels = [
        'augmenter_poids' => 'Augmenter son poids',
        'reduire_poids'   => 'Reduire son poids',
        'imc_ideal'       => 'Atteindre son IMC ideal',
    ];

    public function login(): string|RedirectResponse
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/user');
        }

        $this->ensureSchema();

        return view('login', ['mode' => 'user']);
    }

    public function adminLogin(): string|RedirectResponse
    {
        if (session()->get('isAdminLoggedIn')) {
            return redirect()->to('/admin/dashboard');
        }

        $this->ensureSchema();

        return view('login', ['mode' => 'admin']);
    }

    public function attemptLogin(): RedirectResponse
    {
        $this->ensureSchema();

        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $user = $this->db()->table('utilisateurs')->where('email', $email)->where('actif', 1)->get()->getRowArray();

        if (! $user || ! password_verify($password, $user['mot_de_passe'])) {
            $admin = $this->db()->table('admins')->where('email', $email)->where('actif', 1)->get()->getRowArray();
            if ($admin && password_verify($password, $admin['mot_de_passe'])) {
                return $this->loginAdmin($admin);
            }

            return redirect()->back()->withInput()->with('error', 'Email ou mot de passe incorrect.');
        }

        $this->db()->table('utilisateurs')->where('id', $user['id'])->update(['dernier_login' => date('Y-m-d H:i:s')]);

        session()->set([
            'isLoggedIn' => true,
            'userId'     => (int) $user['id'],
            'userName'   => $user['nom_complet'],
            'userEmail'  => $user['email'],
        ]);

        return redirect()->to('/user');
    }

    public function attemptAdminLogin(): RedirectResponse
    {
        $this->ensureSchema();

        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $admin = $this->db()->table('admins')->where('email', $email)->where('actif', 1)->get()->getRowArray();

        if (! $admin || ! password_verify($password, $admin['mot_de_passe'])) {
            return redirect()->back()->withInput()->with('error', 'Identifiants administrateur incorrects.');
        }

        return $this->loginAdmin($admin);
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    private function loginAdmin(array $admin): RedirectResponse
    {
        session()->set([
            'isAdminLoggedIn' => true,
            'adminId'         => (int) $admin['id'],
            'adminName'       => $admin['nom'],
            'adminEmail'      => $admin['email'],
        ]);

        return redirect()->to(site_url('admin/dashboard'));
    }

    public function registerUser(): string
    {
        $this->ensureSchema();

        return view('register_user');
    }

    public function storeRegisterUser(): RedirectResponse
    {
        $data = [
            'nom_complet'    => trim((string) $this->request->getPost('nom_complet')),
            'email'          => trim((string) $this->request->getPost('email')),
            'mot_de_passe'   => (string) $this->request->getPost('mot_de_passe'),
            'genre'          => (string) $this->request->getPost('genre'),
            'date_naissance' => (string) $this->request->getPost('date_naissance'),
        ];

        if ($data['nom_complet'] === '' || $data['email'] === '' || strlen($data['mot_de_passe']) < 6) {
            return redirect()->back()->withInput()->with('error', 'Veuillez remplir les informations utilisateur. Le mot de passe doit contenir au moins 6 caracteres.');
        }

        if ($this->db()->table('utilisateurs')->where('email', $data['email'])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Cet email est deja utilise.');
        }

        session()->set('register_user', $data);

        return redirect()->to('/inscription/sante');
    }

    public function registerHealth(): string|RedirectResponse
    {
        if (! session()->get('register_user')) {
            return redirect()->to('/inscription')->with('error', 'Commencez par les informations utilisateur.');
        }

        return view('register_health');
    }

    public function storeRegisterHealth(): RedirectResponse
    {
        $base = session()->get('register_user');
        if (! $base) {
            return redirect()->to('/inscription')->with('error', 'Commencez par les informations utilisateur.');
        }

        $taille = (float) $this->request->getPost('taille_cm');
        $poids = (float) $this->request->getPost('poids_kg');
        if ($taille < 100 || $taille > 250 || $poids < 20 || $poids > 300) {
            return redirect()->back()->withInput()->with('error', 'Taille ou poids invalide.');
        }

        $this->ensureSchema();
        $this->db()->table('utilisateurs')->insert([
            'nom_complet'    => $base['nom_complet'],
            'email'          => $base['email'],
            'mot_de_passe'   => password_hash($base['mot_de_passe'], PASSWORD_DEFAULT),
            'genre'          => $base['genre'],
            'date_naissance' => $base['date_naissance'],
            'taille_cm'      => $taille,
            'poids_kg'       => $poids,
            'imc'            => $this->calculateImc($taille, $poids),
            'profil_complet' => 1,
        ]);

        session()->remove('register_user');

        return redirect()->to('/login')->with('success', 'Inscription terminee. Vous pouvez vous connecter.');
    }

    public function imcPreview(): ResponseInterface
    {
        $taille = (float) $this->request->getGet('taille_cm');
        $poids = (float) $this->request->getGet('poids_kg');
        $imc = $this->calculateImc($taille, $poids);

        if ($imc < 18.5) {
            $categorie = 'Insuffisance ponderale';
        } elseif ($imc <= 24.9) {
            $categorie = 'IMC ideal';
        } elseif ($imc <= 29.9) {
            $categorie = 'Surpoids';
        } else {
            $categorie = 'Obesite';
        }

        return $this->response->setJSON([
            'imc'       => $imc,
            'categorie' => $categorie,
        ]);
    }

    public function user(): string|RedirectResponse
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Connexion utilisateur requise.');
        }

        $this->ensureSchema();
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to('/logout');
        }

        $suggestions = $this->suggestionsFor($user);

        return view('user_dashboard', [
            'user'            => $user,
            'objectives'      => $this->objectiveLabels,
            'suggestions'     => $suggestions,
            'goldPrice'       => $this->param('gold_prix', '29.99'),
            'goldDiscount'    => $this->param('gold_remise_pct', '15'),
            'transactions'    => $this->db()->table('transactions_portefeuille')->where('utilisateur_id', $user['id'])->orderBy('created_at', 'DESC')->get(5)->getResultArray(),
        ]);
    }

    public function saveObjective(): RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $objectif = (string) $this->request->getPost('objectif');
        if (! isset($this->objectiveLabels[$objectif])) {
            return redirect()->back()->with('error', 'Objectif invalide.');
        }

        $this->db()->table('utilisateurs')->where('id', session()->get('userId'))->update(['objectif' => $objectif]);

        $user = $this->currentUser();
        $regime = $this->bestRegime($objectif);
        if ($user && $regime) {
            $price = $this->priceForUser((float) $regime['prix'], (int) $user['is_gold']);
            $durationDays = $regime['duree_unite'] === 'mois' ? (int) $regime['duree_valeur'] * 30 : (int) $regime['duree_valeur'] * 7;
            $target = $this->targetWeight((float) $user['poids_kg'], (float) $user['taille_cm'], $objectif, $regime);

            $this->db()->table('programmes')->insert([
                'utilisateur_id' => $user['id'],
                'regime_id'      => $regime['id'],
                'objectif'       => $objectif,
                'date_debut'     => date('Y-m-d'),
                'date_fin'       => date('Y-m-d', strtotime('+' . $durationDays . ' days')),
                'poids_initial'  => $user['poids_kg'],
                'poids_cible'    => $target,
                'prix_paye'      => $price,
                'remise_gold'    => (int) $user['is_gold'],
            ]);
        }

        return redirect()->to('/user')->with('success', 'Objectif enregistre et programme suggere.');
    }

    public function buyGold(): RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $user = $this->currentUser();
        $price = (float) $this->param('gold_prix', '29.99');

        if ((int) $user['is_gold'] === 1) {
            return redirect()->back()->with('error', 'Votre compte est deja Gold.');
        }
        if ((float) $user['solde'] < $price) {
            return redirect()->back()->with('error', 'Solde insuffisant pour acheter Gold.');
        }

        $db = $this->db();
        $newBalance = (float) $user['solde'] - $price;
        $db->transStart();
        $db->table('utilisateurs')->where('id', $user['id'])->update(['solde' => $newBalance, 'is_gold' => 1, 'gold_achat_at' => date('Y-m-d H:i:s')]);
        $db->table('achats_gold')->ignore(true)->insert(['utilisateur_id' => $user['id'], 'montant_paye' => $price]);
        $db->table('transactions_portefeuille')->insert([
            'utilisateur_id' => $user['id'],
            'type'           => 'debit',
            'montant'        => $price,
            'solde_apres'    => $newBalance,
            'motif'          => 'Achat option Gold',
        ]);
        $db->transComplete();

        return redirect()->back()->with('success', 'Option Gold activee. Remise de 15% appliquee aux regimes.');
    }

    public function useWalletCode(): RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $codeValue = strtoupper(trim((string) $this->request->getPost('code')));
        $code = $this->db()->table('codes_portefeuille')->where('code', $codeValue)->get()->getRowArray();

        if (! $code || (int) $code['actif'] !== 1 || ($code['expire_at'] && strtotime($code['expire_at']) < time())) {
            return redirect()->back()->with('error', 'Code invalide ou expire.');
        }
        if ((int) $code['utilisations_max'] > 0 && (int) $code['utilisations_count'] >= (int) $code['utilisations_max']) {
            return redirect()->back()->with('error', 'Ce code a deja atteint sa limite.');
        }

        $user = $this->currentUser();
        $alreadyUsed = $this->db()->table('historique_codes')->where('code_id', $code['id'])->where('utilisateur_id', $user['id'])->countAllResults();
        if ($alreadyUsed > 0) {
            return redirect()->back()->with('error', 'Vous avez deja utilise ce code.');
        }

        $newBalance = (float) $user['solde'] + (float) $code['valeur'];
        $db = $this->db();
        $db->transStart();
        $db->table('historique_codes')->insert(['code_id' => $code['id'], 'utilisateur_id' => $user['id'], 'montant_credite' => $code['valeur']]);
        $db->table('codes_portefeuille')->where('id', $code['id'])->update([
            'utilisations_count' => $db->table('historique_codes')->where('code_id', $code['id'])->countAllResults(),
        ]);
        $db->table('utilisateurs')->where('id', $user['id'])->update(['solde' => $newBalance]);
        $db->table('transactions_portefeuille')->insert([
            'utilisateur_id' => $user['id'],
            'type'           => 'credit',
            'montant'        => $code['valeur'],
            'solde_apres'    => $newBalance,
            'motif'          => 'Code portefeuille ' . $code['code'],
            'reference_id'   => $code['id'],
        ]);
        $db->transComplete();

        return redirect()->back()->with('success', 'Porte monnaie credite de ' . number_format((float) $code['valeur'], 2) . '.');
    }

    public function exportProgram(): string|RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $user = $this->currentUser();

        return view('program_pdf', [
            'user'        => $user,
            'objectives'  => $this->objectiveLabels,
            'suggestions' => $this->suggestionsFor($user),
        ]);
    }

    public function adminDashboard(): string|RedirectResponse
    {
        if (! session()->get('isAdminLoggedIn')) {
            return redirect()->to('/admin')->with('error', 'Connexion administrateur requise.');
        }

        $this->ensureSchema();
        $db = $this->db();

        return view('admin_dashboard', [
            'stats'       => $this->stats(),
            'regimes'     => $db->table('regimes')->orderBy('id', 'DESC')->get()->getResultArray(),
            'activites'   => $db->table('activites a')->select('a.*, c.nom AS categorie')->join('categories_activite c', 'c.id = a.categorie_id', 'left')->orderBy('a.id', 'DESC')->get()->getResultArray(),
            'categories'  => $db->table('categories_activite')->orderBy('nom', 'ASC')->get()->getResultArray(),
            'codes'       => $db->table('codes_portefeuille')->orderBy('id', 'DESC')->get()->getResultArray(),
            'parametres'  => $db->table('parametres')->orderBy('cle', 'ASC')->get()->getResultArray(),
            'objectives'  => $this->objectiveLabels,
        ]);
    }

    public function storeRegime(): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('regimes')->insert($this->regimePayload());

        return redirect()->back()->with('success', 'Regime ajoute.');
    }

    public function updateRegime(int $id): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('regimes')->where('id', $id)->update($this->regimePayload());

        return redirect()->back()->with('success', 'Regime modifie.');
    }

    public function deleteRegime(int $id): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('regimes')->where('id', $id)->update(['actif' => 0]);

        return redirect()->back()->with('success', 'Regime desactive.');
    }

    public function storeActivity(): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('activites')->insert($this->activityPayload());

        return redirect()->back()->with('success', 'Activite ajoutee.');
    }

    public function updateActivity(int $id): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('activites')->where('id', $id)->update($this->activityPayload());

        return redirect()->back()->with('success', 'Activite modifiee.');
    }

    public function deleteActivity(int $id): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('activites')->where('id', $id)->update(['actif' => 0]);

        return redirect()->back()->with('success', 'Activite desactivee.');
    }

    public function storeWalletCode(): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        $this->db()->table('codes_portefeuille')->insert([
            'code'             => strtoupper(trim((string) $this->request->getPost('code'))),
            'valeur'           => (float) $this->request->getPost('valeur'),
            'utilisations_max' => (int) $this->request->getPost('utilisations_max'),
            'expire_at'        => $this->request->getPost('expire_at') ?: null,
            'created_by'       => session()->get('adminId'),
            'actif'            => 1,
        ]);

        return redirect()->back()->with('success', 'Code portefeuille cree.');
    }

    public function updateParametres(): RedirectResponse
    {
        $guard = $this->guardAdmin();
        if ($guard) {
            return $guard;
        }

        foreach ((array) $this->request->getPost('parametres') as $id => $value) {
            $this->db()->table('parametres')->where('id', (int) $id)->update(['valeur' => (string) $value]);
        }

        return redirect()->back()->with('success', 'Parametres mis a jour.');
    }

    private function db(): BaseConnection
    {
        return db_connect();
    }

    private function currentUser(): ?array
    {
        return $this->db()->table('utilisateurs')->where('id', session()->get('userId'))->get()->getRowArray();
    }

    private function guardUser(): ?RedirectResponse
    {
        return session()->get('isLoggedIn') ? null : redirect()->to('/login')->with('error', 'Connexion utilisateur requise.');
    }

    private function guardAdmin(): ?RedirectResponse
    {
        return session()->get('isAdminLoggedIn') ? null : redirect()->to('/admin')->with('error', 'Connexion administrateur requise.');
    }

    private function param(string $key, string $default): string
    {
        $row = $this->db()->table('parametres')->where('cle', $key)->get()->getRowArray();

        return $row['valeur'] ?? $default;
    }

    private function calculateImc(float $tailleCm, float $poidsKg): float
    {
        return $tailleCm > 0 ? round($poidsKg / (($tailleCm / 100) ** 2), 2) : 0.0;
    }

    private function suggestionsFor(array $user): array
    {
        $objectif = $user['objectif'] ?: $this->suggestObjectiveFromImc((float) $user['imc']);
        $regimes = $this->db()->table('regimes')->where('actif', 1)->like('objectif_cible', $objectif)->orderBy('prix', 'ASC')->get(3)->getResultArray();
        $activites = $this->db()->table('activites a')->select('a.*, c.nom AS categorie')->join('categories_activite c', 'c.id = a.categorie_id', 'left')->where('a.actif', 1)->like('a.objectif_compatible', $objectif)->get(4)->getResultArray();

        foreach ($regimes as &$regime) {
            $regime['prix_final'] = $this->priceForUser((float) $regime['prix'], (int) $user['is_gold']);
            $regime['poids_cible'] = $this->targetWeight((float) $user['poids_kg'], (float) $user['taille_cm'], $objectif, $regime);
        }

        return [
            'objectif'  => $objectif,
            'regimes'   => $regimes,
            'activites' => $activites,
        ];
    }

    private function suggestObjectiveFromImc(float $imc): string
    {
        if ($imc < 18.5) {
            return 'augmenter_poids';
        }
        if ($imc > 24.9) {
            return 'reduire_poids';
        }

        return 'imc_ideal';
    }

    private function bestRegime(string $objectif): ?array
    {
        return $this->db()->table('regimes')->where('actif', 1)->like('objectif_cible', $objectif)->orderBy('prix', 'ASC')->get(1)->getRowArray();
    }

    private function priceForUser(float $price, int $isGold): float
    {
        $discount = $isGold === 1 ? (float) $this->param('gold_remise_pct', '15') : 0.0;

        return round($price * (1 - $discount / 100), 2);
    }

    private function targetWeight(float $poids, float $taille, string $objectif, array $regime): float
    {
        if ($objectif === 'augmenter_poids') {
            return round($poids + (float) $regime['variation_poids_max_kg'], 2);
        }
        if ($objectif === 'reduire_poids') {
            return round($poids - abs((float) $regime['variation_poids_max_kg']), 2);
        }

        return round(22 * (($taille / 100) ** 2), 2);
    }

    private function regimePayload(): array
    {
        $objectives = implode(',', (array) $this->request->getPost('objectif_cible'));
        $viande = (float) $this->request->getPost('pct_viande');
        $poisson = (float) $this->request->getPost('pct_poisson');
        $volaille = (float) $this->request->getPost('pct_volaille');
        $autres = max(0, 100 - $viande - $poisson - $volaille);

        return [
            'nom'                    => trim((string) $this->request->getPost('nom')),
            'description'            => trim((string) $this->request->getPost('description')),
            'duree_valeur'           => (int) $this->request->getPost('duree_valeur'),
            'duree_unite'            => (string) $this->request->getPost('duree_unite'),
            'prix'                   => (float) $this->request->getPost('prix'),
            'variation_poids_min_kg' => (float) $this->request->getPost('variation_poids_min_kg'),
            'variation_poids_max_kg' => (float) $this->request->getPost('variation_poids_max_kg'),
            'pct_viande'             => $viande,
            'pct_poisson'            => $poisson,
            'pct_volaille'           => $volaille,
            'pct_autres'             => $autres,
            'objectif_cible'         => $objectives,
            'actif'                  => 1,
        ];
    }

    private function activityPayload(): array
    {
        return [
            'categorie_id'          => (int) $this->request->getPost('categorie_id'),
            'nom'                   => trim((string) $this->request->getPost('nom')),
            'description'           => trim((string) $this->request->getPost('description')),
            'intensite'             => (string) $this->request->getPost('intensite'),
            'calories_par_heure'    => (int) $this->request->getPost('calories_par_heure'),
            'frequence_semaine'     => (int) $this->request->getPost('frequence_semaine'),
            'objectif_compatible'   => implode(',', (array) $this->request->getPost('objectif_compatible')),
            'actif'                 => 1,
        ];
    }

    private function stats(): array
    {
        $db = $this->db();

        return [
            'users'      => $db->table('utilisateurs')->countAllResults(),
            'gold'       => $db->table('utilisateurs')->where('is_gold', 1)->countAllResults(),
            'programmes' => $db->table('programmes')->countAllResults(),
            'ca'         => (float) ($db->table('programmes')->selectSum('prix_paye')->get()->getRowArray()['prix_paye'] ?? 0),
            'objectifs'  => $db->table('utilisateurs')->select('objectif, COUNT(*) AS total')->where('objectif IS NOT NULL')->groupBy('objectif')->get()->getResultArray(),
            'genre'      => $db->table('utilisateurs')->select('genre, objectif, COUNT(*) AS total')->where('objectif IS NOT NULL')->groupBy('genre, objectif')->get()->getResultArray(),
        ];
    }

    private function ensureSchema(): void
    {
        $db = $this->db();
        $forge = \Config\Database::forge();

        if (! $db->tableExists('parametres')) {
            $db->query("CREATE TABLE parametres (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cle VARCHAR(100) NOT NULL UNIQUE, valeur VARCHAR(255) NOT NULL, description VARCHAR(255), updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('admins')) {
            $db->query("CREATE TABLE admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, mot_de_passe VARCHAR(255) NOT NULL, role ENUM('superadmin','admin') DEFAULT 'admin', actif TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('utilisateurs')) {
            $db->query("CREATE TABLE utilisateurs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nom_complet VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, mot_de_passe VARCHAR(255) NOT NULL, genre ENUM('homme','femme','autre') NOT NULL, date_naissance DATE NOT NULL, taille_cm DECIMAL(5,2) NOT NULL, poids_kg DECIMAL(5,2) NOT NULL, imc DECIMAL(5,2) DEFAULT NULL, objectif ENUM('augmenter_poids','reduire_poids','imc_ideal') DEFAULT NULL, photo_url VARCHAR(500) DEFAULT NULL, profil_complet TINYINT(1) DEFAULT 0, solde DECIMAL(10,2) DEFAULT 0.00, is_gold TINYINT(1) DEFAULT 0, gold_achat_at DATETIME DEFAULT NULL, actif TINYINT(1) DEFAULT 1, email_verifie TINYINT(1) DEFAULT 0, token_reset VARCHAR(255) DEFAULT NULL, token_expire DATETIME DEFAULT NULL, dernier_login DATETIME DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('categories_activite')) {
            $db->query("CREATE TABLE categories_activite (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(100) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('activites')) {
            $db->query("CREATE TABLE activites (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, categorie_id INT UNSIGNED NOT NULL, nom VARCHAR(150) NOT NULL, description TEXT, intensite ENUM('faible','moderee','elevee') NOT NULL, calories_par_heure INT UNSIGNED DEFAULT 0, frequence_semaine TINYINT UNSIGNED DEFAULT 3, objectif_compatible VARCHAR(100) NOT NULL, actif TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('regimes')) {
            $db->query("CREATE TABLE regimes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(150) NOT NULL, description TEXT, duree_valeur TINYINT UNSIGNED NOT NULL, duree_unite ENUM('semaines','mois') NOT NULL DEFAULT 'semaines', prix DECIMAL(8,2) NOT NULL, variation_poids_min_kg DECIMAL(5,2) NOT NULL, variation_poids_max_kg DECIMAL(5,2) NOT NULL, pct_viande DECIMAL(5,2) NOT NULL DEFAULT 0.00, pct_poisson DECIMAL(5,2) NOT NULL DEFAULT 0.00, pct_volaille DECIMAL(5,2) NOT NULL DEFAULT 0.00, pct_autres DECIMAL(5,2) NOT NULL DEFAULT 0.00, objectif_cible VARCHAR(100) NOT NULL, actif TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('programmes')) {
            $db->query("CREATE TABLE programmes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, utilisateur_id INT UNSIGNED NOT NULL, regime_id INT UNSIGNED NOT NULL, objectif ENUM('augmenter_poids','reduire_poids','imc_ideal') NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, poids_initial DECIMAL(5,2) NOT NULL, poids_cible DECIMAL(5,2) NOT NULL, prix_paye DECIMAL(8,2) NOT NULL, remise_gold TINYINT(1) DEFAULT 0, statut ENUM('actif','termine','annule') DEFAULT 'actif', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('codes_portefeuille')) {
            $db->query("CREATE TABLE codes_portefeuille (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NOT NULL UNIQUE, valeur DECIMAL(8,2) NOT NULL, utilisations_max INT UNSIGNED DEFAULT 1, utilisations_count INT UNSIGNED DEFAULT 0, actif TINYINT(1) DEFAULT 1, expire_at DATETIME DEFAULT NULL, created_by INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('historique_codes')) {
            $db->query("CREATE TABLE historique_codes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code_id INT UNSIGNED NOT NULL, utilisateur_id INT UNSIGNED NOT NULL, montant_credite DECIMAL(8,2) NOT NULL, utilise_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('transactions_portefeuille')) {
            $db->query("CREATE TABLE transactions_portefeuille (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, utilisateur_id INT UNSIGNED NOT NULL, type ENUM('credit','debit') NOT NULL, montant DECIMAL(8,2) NOT NULL, solde_apres DECIMAL(8,2) NOT NULL, motif VARCHAR(255), reference_id INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $db->tableExists('achats_gold')) {
            $db->query("CREATE TABLE achats_gold (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, utilisateur_id INT UNSIGNED NOT NULL UNIQUE, montant_paye DECIMAL(8,2) NOT NULL, achete_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        unset($forge);
        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $db = $this->db();
        $params = [
            ['gold_prix', '29.99', 'Prix unique de l option Gold'],
            ['gold_remise_pct', '15', 'Remise Gold sur les regimes'],
        ];
        foreach ($params as $param) {
            if ($db->table('parametres')->where('cle', $param[0])->countAllResults() === 0) {
                $db->table('parametres')->insert(['cle' => $param[0], 'valeur' => $param[1], 'description' => $param[2]]);
            }
        }

        $defaultAdmin = $db->table('admins')->where('email', 'admin@app.com')->get()->getRowArray();
        if (! $defaultAdmin) {
            $db->table('admins')->insert([
                'nom'          => 'Super Admin',
                'email'        => 'admin@app.com',
                'mot_de_passe' => password_hash('Admin1234!', PASSWORD_DEFAULT),
                'role'         => 'superadmin',
            ]);
        } elseif (! password_verify('Admin1234!', (string) ($defaultAdmin['mot_de_passe'] ?? ''))) {
            $db->table('admins')->where('id', $defaultAdmin['id'])->update([
                'mot_de_passe' => password_hash('Admin1234!', PASSWORD_DEFAULT),
            ]);
        }

        foreach (['Cardio', 'Musculation', 'Yoga / Stretching', 'Natation', 'Endurance'] as $category) {
            if ($db->table('categories_activite')->where('nom', $category)->countAllResults() === 0) {
                $db->table('categories_activite')->insert(['nom' => $category]);
            }
        }

        $regimes = [
            ['nom' => 'Equilibre proteine', 'description' => 'Repas riches et structures pour une prise de poids progressive.', 'duree_valeur' => 8, 'duree_unite' => 'semaines', 'prix' => 120, 'variation_poids_min_kg' => 2, 'variation_poids_max_kg' => 4, 'pct_viande' => 35, 'pct_poisson' => 20, 'pct_volaille' => 25, 'pct_autres' => 20, 'objectif_cible' => 'augmenter_poids,imc_ideal'],
            ['nom' => 'Leger controle', 'description' => 'Deficit calorique doux avec repas varies.', 'duree_valeur' => 6, 'duree_unite' => 'semaines', 'prix' => 95, 'variation_poids_min_kg' => 2, 'variation_poids_max_kg' => 5, 'pct_viande' => 20, 'pct_poisson' => 35, 'pct_volaille' => 25, 'pct_autres' => 20, 'objectif_cible' => 'reduire_poids,imc_ideal'],
            ['nom' => 'IMC stable', 'description' => 'Plan de stabilisation pour rester dans la zone ideale.', 'duree_valeur' => 1, 'duree_unite' => 'mois', 'prix' => 80, 'variation_poids_min_kg' => 0, 'variation_poids_max_kg' => 2, 'pct_viande' => 25, 'pct_poisson' => 25, 'pct_volaille' => 25, 'pct_autres' => 25, 'objectif_cible' => 'imc_ideal'],
            ['nom' => 'Force et masse', 'description' => 'Programme hypercalorique accompagne de musculation.', 'duree_valeur' => 12, 'duree_unite' => 'semaines', 'prix' => 150, 'variation_poids_min_kg' => 3, 'variation_poids_max_kg' => 6, 'pct_viande' => 40, 'pct_poisson' => 15, 'pct_volaille' => 25, 'pct_autres' => 20, 'objectif_cible' => 'augmenter_poids'],
            ['nom' => 'Seche active', 'description' => 'Plan court pour reduire le poids avec activite cardio.', 'duree_valeur' => 2, 'duree_unite' => 'mois', 'prix' => 135, 'variation_poids_min_kg' => 4, 'variation_poids_max_kg' => 8, 'pct_viande' => 15, 'pct_poisson' => 40, 'pct_volaille' => 25, 'pct_autres' => 20, 'objectif_cible' => 'reduire_poids'],
        ];
        foreach ($regimes as $regime) {
            if ($db->table('regimes')->where('nom', $regime['nom'])->countAllResults() === 0) {
                $db->table('regimes')->insert($regime);
            }
        }

        $cardio = $db->table('categories_activite')->where('nom', 'Cardio')->get()->getRowArray()['id'] ?? 1;
        $muscu = $db->table('categories_activite')->where('nom', 'Musculation')->get()->getRowArray()['id'] ?? 1;
        $yoga = $db->table('categories_activite')->where('nom', 'Yoga / Stretching')->get()->getRowArray()['id'] ?? 1;
        $natation = $db->table('categories_activite')->where('nom', 'Natation')->get()->getRowArray()['id'] ?? 1;
        $endurance = $db->table('categories_activite')->where('nom', 'Endurance')->get()->getRowArray()['id'] ?? 1;
        $activites = [
            ['categorie_id' => $cardio, 'nom' => 'Marche rapide', 'description' => '45 minutes par seance.', 'intensite' => 'moderee', 'calories_par_heure' => 280, 'frequence_semaine' => 4, 'objectif_compatible' => 'reduire_poids,imc_ideal'],
            ['categorie_id' => $muscu, 'nom' => 'Renforcement complet', 'description' => 'Seances haut/bas du corps.', 'intensite' => 'moderee', 'calories_par_heure' => 320, 'frequence_semaine' => 3, 'objectif_compatible' => 'augmenter_poids,imc_ideal'],
            ['categorie_id' => $yoga, 'nom' => 'Mobilite et respiration', 'description' => 'Recuperation et stabilite.', 'intensite' => 'faible', 'calories_par_heure' => 150, 'frequence_semaine' => 2, 'objectif_compatible' => 'augmenter_poids,reduire_poids,imc_ideal'],
            ['categorie_id' => $natation, 'nom' => 'Natation douce', 'description' => 'Travail complet sans impact.', 'intensite' => 'moderee', 'calories_par_heure' => 420, 'frequence_semaine' => 2, 'objectif_compatible' => 'reduire_poids,imc_ideal'],
            ['categorie_id' => $endurance, 'nom' => 'Course fractionnee', 'description' => 'Alternance effort et recuperation.', 'intensite' => 'elevee', 'calories_par_heure' => 650, 'frequence_semaine' => 2, 'objectif_compatible' => 'reduire_poids'],
        ];
        foreach ($activites as $activite) {
            if ($db->table('activites')->where('nom', $activite['nom'])->countAllResults() === 0) {
                $db->table('activites')->insert($activite);
            }
        }

        $demoUsers = [
            ['nom_complet' => 'Andry Rakoto', 'email' => 'andry@example.com', 'genre' => 'homme', 'date_naissance' => '1998-04-12', 'taille_cm' => 174, 'poids_kg' => 62, 'objectif' => 'augmenter_poids', 'solde' => 80],
            ['nom_complet' => 'Miora Rabe', 'email' => 'miora@example.com', 'genre' => 'femme', 'date_naissance' => '2001-09-18', 'taille_cm' => 164, 'poids_kg' => 78, 'objectif' => 'reduire_poids', 'solde' => 120],
            ['nom_complet' => 'Hery Randria', 'email' => 'hery@example.com', 'genre' => 'homme', 'date_naissance' => '1995-01-03', 'taille_cm' => 180, 'poids_kg' => 82, 'objectif' => 'imc_ideal', 'solde' => 45],
            ['nom_complet' => 'Sitraka Raoelina', 'email' => 'sitraka@example.com', 'genre' => 'autre', 'date_naissance' => '1999-07-24', 'taille_cm' => 170, 'poids_kg' => 68, 'objectif' => 'imc_ideal', 'solde' => 60],
            ['nom_complet' => 'Fanja Nomenjanahary', 'email' => 'fanja@example.com', 'genre' => 'femme', 'date_naissance' => '1997-11-30', 'taille_cm' => 158, 'poids_kg' => 49, 'objectif' => 'augmenter_poids', 'solde' => 100],
        ];
        foreach ($demoUsers as $user) {
            if ($db->table('utilisateurs')->where('email', $user['email'])->countAllResults() === 0) {
                $user['mot_de_passe'] = password_hash('password', PASSWORD_DEFAULT);
                $user['imc'] = $this->calculateImc((float) $user['taille_cm'], (float) $user['poids_kg']);
                $user['profil_complet'] = 1;
                $db->table('utilisateurs')->insert($user);
            }
        }

        $adminId = $db->table('admins')->where('email', 'admin@app.com')->get()->getRowArray()['id'] ?? 1;
        $codes = [
            ['WELCOME50', 50], ['GOLD30', 30], ['SPORT25', 25], ['HEALTH40', 40], ['IMC20', 20],
            ['REGIME60', 60], ['ACTIVE15', 15], ['BOOST75', 75], ['FIT10', 10], ['EKOTRANA100', 100],
            ['BALANCE35', 35], ['CARDIO45', 45], ['MUSCU55', 55], ['ZEN25', 25], ['START80', 80],
        ];
        foreach ($codes as $code) {
            if ($db->table('codes_portefeuille')->where('code', $code[0])->countAllResults() === 0) {
                $db->table('codes_portefeuille')->insert([
                    'code'             => $code[0],
                    'valeur'           => $code[1],
                    'utilisations_max' => 100,
                    'created_by'       => $adminId,
                ]);
            }
        }
    }
}
