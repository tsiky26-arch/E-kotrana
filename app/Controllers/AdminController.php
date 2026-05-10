<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class AdminController extends AppController
{
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
}
