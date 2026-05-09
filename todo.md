# TODO — Application Santé & Nutrition

---

## 🔐 AUTHENTIFICATION & INSCRIPTION

### Page 1 — Informations Personnelles
- [ ] Champ : Nom complet
- [ ] Champ : Adresse email
- [ ] Champ : Mot de passe + confirmation
- [ ] Champ : Genre (Homme / Femme / Autre)
- [ ] Champ : Date de naissance
- [ ] Bouton "Suivant" → redirection vers Page 2

### Page 2 — Informations de Santé
- [ ] Champ : Taille (cm)
- [ ] Champ : Poids actuel (kg)
- [ ] Calcul automatique de l'IMC
- [ ] Bouton "Terminer l'inscription"

### Login
- [ ] Page de connexion (email + mot de passe)
- [ ] Lien "Mot de passe oublié"
- [ ] Redirection vers le tableau de bord après connexion

---

## 👤 PROFIL UTILISATEUR

### Complétion du profil
- [ ] Page de complétion du profil (photo, informations complémentaires)
- [ ] Affichage de l'IMC actuel calculé
- [ ] Indicateur de progression du profil (%)

### Choix de l'objectif (3 options)
- [ ] Option 1 : Augmenter son poids
- [ ] Option 2 : Réduire son poids
- [ ] Option 3 : Atteindre son IMC idéal
- [ ] Affichage d'un seul objectif actif à la fois
- [ ] Possibilité de modifier l'objectif depuis le profil

---

## 💡 SUGGESTIONS PERSONNALISÉES

- [ ] Algorithme de suggestion de régime selon l'objectif choisi
- [ ] Algorithme de suggestion d'activité sportive selon l'objectif
- [ ] Affichage d'une durée estimée pour atteindre l'objectif
- [ ] Affichage du programme complet (régime + sport + durée)

---

## 📄 EXPORT PDF

- [ ] Bouton "Exporter en PDF" sur la page de programme
- [ ] PDF inclut : profil, objectif, régime suggéré, activités sportives, durée
- [ ] Mise en page soignée du PDF exporté

---

## 💰 PORTE-MONNAIE

- [ ] Affichage du solde du porte-monnaie sur le profil
- [ ] Champ de saisie d'un code de recharge
- [ ] Validation du code côté serveur (appel Back Office)
- [ ] Message de succès / erreur après validation
- [ ] Historique des recharges

---

## ⭐ OPTION GOLD

- [ ] Affichage de l'offre Gold (prix unique à définir, ex : 29,99€)
- [ ] Page de présentation des avantages Gold
- [ ] Paiement en une seule fois
- [ ] Activation de la remise 15% sur tous les régimes après achat
- [ ] Badge "Gold" affiché sur le profil utilisateur
- [ ] Vérification du statut Gold lors de l'achat d'un régime

---

## 🖥️ BACK OFFICE — GÉNÉRAL

### Authentification Admin
- [ ] Page de login administrateur (email + mot de passe)
- [ ] Gestion des sessions admin sécurisées
- [ ] Déconnexion

### Tableau de Bord & Statistiques
- [ ] Nombre total d'utilisateurs inscrits
- [ ] Nombre d'utilisateurs actifs (30 derniers jours)
- [ ] Nombre d'utilisateurs Gold
- [ ] Graphe : évolution des inscriptions (courbe par mois)
- [ ] Graphe : répartition des objectifs (camembert)
- [ ] Graphe : régimes les plus achetés (barres)
- [ ] Tableau croisé : objectif × régime choisi
- [ ] Tableau croisé : genre × objectif
- [ ] Chiffre d'affaires total et par mois

---

## 🥗 BACK OFFICE — CRUD RÉGIMES

### Liste des régimes
- [ ] Tableau listant tous les régimes (nom, durée, prix, statut)
- [ ] Filtres : par durée, par objectif (prise/perte de poids)
- [ ] Recherche par nom

### Création / Édition d'un régime
- [ ] Champ : Nom du régime
- [ ] Champ : Description
- [ ] Champ : Durée (en semaines/mois)
- [ ] Champ : Prix (variant selon la durée)
- [ ] Champ : Variation de poids possible (en + et en -)
- [ ] **Composition nutritionnelle :**
  - [ ] % Viande (rouge)
  - [ ] % Poisson
  - [ ] % Volaille
  - [ ] % Légumes / Féculents / Autres (complément à 100%)
  - [ ] Validation : la somme des % doit être égale à 100%
- [ ] Champ : Objectif cible (prise de poids / perte de poids / IMC idéal)
- [ ] Bouton Enregistrer / Annuler

### Suppression
- [ ] Confirmation avant suppression d'un régime
- [ ] Vérification : ne pas supprimer un régime actif dans un programme utilisateur

---

## 🏃 BACK OFFICE — CRUD ACTIVITÉS SPORTIVES

### Liste des activités
- [ ] Tableau listant toutes les activités (nom, catégorie, intensité)
- [ ] Filtres par catégorie (cardio, musculation, yoga, etc.)

### Création / Édition d'une activité
- [ ] Champ : Nom de l'activité
- [ ] Champ : Description
- [ ] Champ : Catégorie
- [ ] Champ : Intensité (faible / modérée / élevée)
- [ ] Champ : Calories brûlées / heure (estimation)
- [ ] Champ : Fréquence recommandée (ex : 3x/semaine)
- [ ] Champ : Compatible avec objectif (prise / perte / IMC)

### Suppression
- [ ] Confirmation avant suppression

---

## 🎫 BACK OFFICE — CODES PORTE-MONNAIE

- [ ] Formulaire de création de code (valeur en €, nombre d'utilisations max)
- [ ] Liste de tous les codes créés (code, valeur, utilisations, statut)
- [ ] Activation / Désactivation d'un code
- [ ] Historique : quel utilisateur a utilisé quel code

---

## ⚙️ BACK OFFICE — CRUD PARAMÈTRES

- [ ] Paramètre : Prix de l'option Gold
- [ ] Paramètre : Taux de remise Gold (actuellement 15%)
- [ ] Paramètre : Plages d'IMC (insuffisant / normal / surpoids / obèse)
- [ ] Paramètre : Limites min/max de taille et poids acceptées
- [ ] Paramètre : Durées disponibles pour les régimes
- [ ] Paramètre : Catégories d'activités sportives
- [ ] Bouton "Sauvegarder les paramètres"

---

## 🔧 TECHNIQUE & TRANSVERSAL

- [ ] Responsive design (mobile + desktop)
- [ ] Gestion des erreurs et messages utilisateur
- [ ] Sécurisation des routes (authentification requise)
- [ ] Séparation Front Office / Back Office (routes ou sous-domaine)
- [ ] Base de données : modèles Utilisateur, Régime, Activité, Code, Paramètre
- [ ] API REST ou équivalent entre frontend et backend
- [ ] Système de rôles : Utilisateur / Administrateur

---

> **Légende :** `[ ]` = À faire &nbsp;|&nbsp; `[x]` = Terminé &nbsp;|&nbsp; `[~]` = En cours