# 🐝 Florametrics

Application web de gestion des ruches et des fanages apicoles, destinée aux agents de terrain et aux administrateurs.

---

## 📋 Description

Florametrics est une application web interne développée en **PHP / MySQL**. Elle permet de gérer des ruches, de suivre les sessions de fanage, d'administrer les utilisateurs et d'exporter des rapports PDF. L'accès est sécurisé par authentification avec gestion des rôles.

---

## ✨ Fonctionnalités

- **Authentification sécurisée** — connexion par login/mot de passe avec vérification du statut du compte
- **Tableau de bord des ruches** — liste des ruches assignées à l'agent connecté (ou toutes les ruches pour un Admin)
- **Gestion des fanages** — saisie, continuation et visualisation des sessions de fanage par ruche
- **Ajout et suppression de ruches** — formulaire d'ajout et suppression sécurisée
- **Gestion des utilisateurs** — interface Admin pour créer, modifier et désactiver des comptes
- **Export PDF** — génération de rapports PDF via la bibliothèque **Dompdf**, avec filtres par date
- **Mode maintenance** — système de maintenance activable depuis la base de données, avec whitelist d'IPs
- **Gestion des rôles** — rôle `Admin` avec accès complet, rôle agent avec accès restreint à ses propres ruches
- **Téléchargement de fichiers** — module de téléchargement sécurisé
- **Protection CSRF** — token CSRF généré à la connexion

---

## 🗂️ Structure du projet

```
florametrics/
├── index.php              # Tableau de bord principal (liste des ruches)
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── fanages.php            # Gestion des sessions de fanage
├── ajouter_ruche.php      # Formulaire d'ajout de ruche
├── delete_ruches.php      # Suppression d'une ruche
├── delete_fanage.php      # Suppression d'un fanage
├── edit_user.php          # Modification de profil utilisateur
├── manage_users.php       # Administration des utilisateurs (Admin)
├── export.php             # Export PDF des rapports
├── download.php           # Téléchargement de fichiers
├── config.php             # Configuration BDD et constantes (à placer hors du webroot)
├── styles.css             # Feuille de styles globale
├── include/
│   ├── header.php         # En-tête commun (navigation)
│   └── footer.php         # Pied de page commun
├── lib/
│   └── dompdf/            # Bibliothèque Dompdf (génération PDF)
└── ressources/
    ├── logo.png
    ├── android.svg
    └── ios.svg
```

---

## ⚙️ Prérequis

- **PHP** 8.0 ou supérieur
- **MySQL** 5.7 ou supérieur
- Serveur web **Apache** ou **Nginx**
- Extension PHP : `mysqli`, `pdo_mysql`, `mbstring`, `gd`
- **Composer** (optionnel, Dompdf est déjà inclus dans `lib/`)

---

## 🚀 Installation

1. **Cloner ou déposer les fichiers** dans le répertoire de votre serveur web (ex : `/var/www/html/florametrics/`).

2. **Configurer la base de données** — éditer `config.php` avec vos propres identifiants :
   ```php
   define('DB_NAME',     'votre_base');
   define('DB_USER',     'votre_utilisateur');
   define('DB_PASSWORD', 'votre_mot_de_passe');
   define('DB_HOST',     'localhost');
   ```
   > ⚠️ **Important :** Pour la sécurité, placez `config.php` en dehors du webroot et ajustez les chemins `require`.

3. **Importer la base de données** — importer votre fichier SQL contenant les tables suivantes :
   - `florametrics` (utilisateurs)
   - `ruches` (ruches)
   - `agent_ruches` (affectations agent ↔ ruche)
   - `ent_fanages` (en-têtes de sessions de fanage)
   - `det_fanages` (détails de sessions de fanage)
   - `maintenance_site` (état de la maintenance)

4. **Configurer les IPs autorisées en mode maintenance** — créer un fichier `authorized_ips.ini` à la racine :
   ```ini
   [maintenance]
   allow_ips[] = "127.0.0.1"
   allow_ips[] = "votre.ip.ici"
   ```

5. **Vérifier les permissions** des dossiers si Dompdf génère des fichiers temporaires.

---

## 🔐 Sécurité

> ⚠️ Avant toute mise en production, veillez à :

- **Ne jamais exposer `config.php`** publiquement — le déplacer hors du webroot.
- **Changer le mot de passe** de la base de données et le `API_TOKEN` dans `config.php`.
- **Désactiver l'affichage des erreurs PHP** en production (`display_errors = Off`).
- **Utiliser HTTPS** pour chiffrer les communications.
- Changer le token d'accès admin `X-Admin-Token` (actuellement en clair dans `config.php`).

---

## 👥 Rôles utilisateurs

| Rôle    | Accès                                                              |
|---------|--------------------------------------------------------------------|
| `Admin` | Toutes les ruches, tous les agents, gestion des utilisateurs       |
| Agent   | Uniquement ses ruches assignées avec statut actif (`A`)            |

---

## 📦 Dépendances incluses

| Bibliothèque | Version | Usage |
|---|---|---|
| [Dompdf](https://github.com/dompdf/dompdf) | incluse dans `lib/` | Génération de PDF |
| php-font-lib | incluse | Support des polices pour Dompdf |
| php-svg-lib | incluse | Support SVG pour Dompdf |
| masterminds/html5 | incluse | Parsing HTML5 pour Dompdf |

---

## 🕐 Fuseau horaire

Le projet est configuré pour le fuseau horaire **Indian/Reunion** (`UTC+4`).
Pour modifier cela, éditer la ligne suivante dans `config.php` :
```php
date_default_timezone_set('Indian/Reunion');
```

---

## 📄 Licence

Projet interne — tous droits réservés.
