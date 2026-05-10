<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends AppController
{
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
}
