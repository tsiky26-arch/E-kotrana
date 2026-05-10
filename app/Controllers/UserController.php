<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class UserController extends AppController
{
    public function profile(): string|RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $this->ensureSchema();
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to('/logout');
        }

        $history = $this->db()->table('historique_poids')
            ->where('utilisateur_id', $user['id'])
            ->orderBy('enregistre_at', 'DESC')
            ->get(10)
            ->getResultArray();

        return view('user_profile', [
            'user'    => $user,
            'history' => $history,
        ]);
    }

    public function updateProfile(): RedirectResponse
    {
        $guard = $this->guardUser();
        if ($guard) {
            return $guard;
        }

        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to('/logout');
        }

        $taille = (float) $this->request->getPost('taille_cm');
        $poids = (float) $this->request->getPost('poids_kg');
        if ($taille < 100 || $taille > 250 || $poids < 20 || $poids > 300) {
            return redirect()->back()->withInput()->with('error', 'Taille ou poids invalide.');
        }

        $nom = trim((string) $this->request->getPost('nom_complet'));
        $genre = (string) $this->request->getPost('genre');
        $dateNaissance = (string) $this->request->getPost('date_naissance');
        $objectif = (string) $this->request->getPost('objectif');
        if ($nom === '' || ! in_array($genre, ['homme', 'femme', 'autre'], true)) {
            return redirect()->back()->withInput()->with('error', 'Informations profil invalides.');
        }

        $imc = $this->calculateImc($taille, $poids);
        $db = $this->db();
        $db->transStart();
        $db->table('utilisateurs')->where('id', $user['id'])->update([
            'nom_complet'    => $nom,
            'genre'          => $genre,
            'date_naissance' => $dateNaissance,
            'taille_cm'      => $taille,
            'poids_kg'       => $poids,
            'imc'            => $imc,
            'objectif'       => isset($this->objectiveLabels[$objectif]) ? $objectif : null,
            'profil_complet' => 1,
        ]);

        if ((float) $user['poids_kg'] !== $poids) {
            $db->table('historique_poids')->insert([
                'utilisateur_id' => $user['id'],
                'poids_kg'       => $poids,
                'imc'            => $imc,
                'note'           => 'Mise a jour profil',
            ]);
        }
        $db->transComplete();

        return redirect()->to('/user/profil')->with('success', 'Profil mis a jour.');
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
            $programmeId = (int) $this->db()->insertID();
            $activites = $this->db()->table('activites')
                ->where('actif', 1)
                ->like('objectif_compatible', $objectif)
                ->orderBy('calories_par_heure', 'DESC')
                ->get(3)
                ->getResultArray();
            foreach ($activites as $activite) {
                $this->db()->table('programme_activites')->ignore(true)->insert([
                    'programme_id' => $programmeId,
                    'activite_id'  => $activite['id'],
                ]);
            }
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
}
