# Convention de Codage – TerrainBook

## Introduction

### Objectif du Document

Ce document établit la convention de codage standardisée pour le projet **TerrainBook**. Il définit les règles et pratiques obligatoires pour garantir :

- **Cohérence** : Code uniforme entre tous les développeurs
- **Maintenabilité** : Facilité de lecture et modification
- **Qualité** : Respect des standards professionnels
- **Collaboration** : Communication claire au sein de l'équipe

### Portée d'Application

Cette convention s'applique à :
- Code backend (PHP)
- Code frontend (JavaScript, HTML, CSS,Tailwind CSS)
- Structure de la base de données
- Organisation des fichiers et dossiers
- Documentation et commentaires

---

## Règles de Langue

### Règle Fondamentale

| Élément | Langue | Justification |
|---------|--------|---------------|
| Fichiers / Dossiers | **Anglais** | Standard international, compatibilité serveurs |
| Variables / Fonctions | **Anglais** | Convention universelle du développement |
| Commentaires / Documentation | **Anglais** | Partage international, documentation technique |
| Base de Données | **Français** | Conformité métier, données localisées |

**Règle d'Or :** *Code en anglais, données métier en français.*

---

## Architecture du Projet

### Structure des Dossiers (Anglais)

```
TP4_WEB/
│
├── actions/              # Scripts de traitement backend
│   ├── admin-manager/      # Actions admin/responsable
│   ├── auth/             # Authentification
│   └── player/           # Actions joueur
│
├── assets/               # Ressources statiques
│   ├── images/
│   └── js/
│   └──css/
│
├── config/               # Configuration
│
├── includes/             # Composants réutilisables
│
└── views/                # Interfaces utilisateur
    ├── admin-manager/
    ├── auth/
    └── player/
```

### Principes Organisationnels

#### Séparation des Responsabilités
- **actions/** : Logique métier et traitement
- **views/** : Présentation et interface
- **config/** : Configuration centralisée
- **includes/** : Composants partagés

#### Organisation par Rôle
- **admin-manager/** : Fonctionnalités administration
- **player/** : Fonctionnalités joueur
- **auth/** : Authentification commune

---

## Conventions de Nommage

### Fichiers et Dossiers

#### Dossiers (lowercase-hyphen)
Format : lowercase avec tirets


```
admin-manager/
```

#### Fichiers PHP

**Actions CRUD :** `action_entity.php`


```
add_stades.php
edit_user.php
delete_reservation.php
get_stades.php
```

**Vues :** `entity-description.php` (pluriel pour listes)


``
stades.php
dashboard.php
```

**Actions Métier :** `verb_object.php`


```
add_stades.php
delete_slot.php
```

### Variables

#### PHP (camelCase)

```php
$currentTerrainId
$userEmail
$uploadDirectory
$isAvailable
$totalPrice
```

#### JavaScript (camelCase)

```js
let currentTerrainId
const uploadPreview
let isFormValid
```

#### Constantes (SCREAMING_SNAKE_CASE)

```php
MAX_FILE_SIZE
UPLOAD_DIR
SESSION_TIMEOUT
```

### Fonctions

**Nommage :** camelCase + verbe d'action


```php
function processImageUpload()
function validateUserInput()
function calculateTotalPrice()
function sendEmailNotification()
```

---

## Conventions PHP

### Structure Standard des Fichiers

Ordre obligatoire :
1. Inclusion des dépendances
2. Vérification d'authentification
3. Configuration des headers
4. Validation de la méthode HTTP
5. Logique métier
6. Gestion des erreurs
7. Réponse JSON

### Nommage des Fonctions

**Format :** `verbObject()` en camelCase

**Exemples :**
- `getTerrainById()`
- `createReservation()`
- `validateEmail()`
- `processPayment()`
- `sendNotification()`

### Sécurité

**Obligatoire :**
- Utiliser des prepared statements pour SQL
- Valider toutes les entrées utilisateur
- Échapper les sorties HTML
- Vérifier les permissions
- Logger les erreurs sans exposer de détails

---

## Conventions JavaScript

### Organisation du Code

Ordre recommandé :
1. Variables globales
2. Références DOM
3. Initialisation (DOMContentLoaded)
4. Event listeners
5. Fonctions AJAX
6. Fonctions utilitaires

### Nommage

**Variables et Fonctions :** camelCase en anglais


```js
let currentTerrainId
function loadTerrains()
function handleFormSubmit()
function showNotification()
```

**Constantes :** SCREAMING_SNAKE_CASE


```js
const MAX_FILE_SIZE = 5242880
```

### AJAX

**Format standard :**
- Utiliser XMLHttpRequest
- Toujours gérer onload, onerror, ontimeout
- Parser les réponses JSON avec try-catch
- Afficher des messages d'erreur utilisateur

### Documentation

Commentaires en anglais pour :
- Sections de code
- Fonctions complexes
- Algorithmes non évidents

---

## Conventions Base de Données

### Tables (Français - snake_case - singulier)


```
terrain
utilisateur
reservation
creneau
equipe
```

### Colonnes (Français - snake_case)

**Clés primaires :**
```sql
id_terrain
id_utilisateur
id_reservation
```

**Clés étrangères :**
```sql
id_responsable  (prefixe id_)
id_joueur
id_equipe
```

**Attributs :**
```sql
nom_te          (nom du terrain)
prenom
prix_heure
date_debut
heure_fin
disponibilite
localisation
```

### Types de Données

| Type | Utilisation |
|------|-------------|
| INT | AUTO_INCREMENT PRIMARY KEY pour IDs |
| VARCHAR(n) | Texte court (nom, email, etc.) |
| TEXT | Texte long (description) |
| DECIMAL(10,2) | Prix, montants |
| DATE | Dates au format YYYY-MM-DD |
| TIME | Heures au format HH:MM:SS |
| TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| ENUM | Valeurs pré-définies |

### Contraintes

**Obligatoire :**
- Définir les clés étrangères avec FOREIGN KEY
- Utiliser ON DELETE et ON UPDATE appropriés
- Créer des index sur les colonnes fréquemment recherchées
- Ajouter NOT NULL quand pertinent

---

## Documentation et Commentaires

### Règle Absolue

**TOUS les commentaires et documentation DOIVENT être en anglais.**

### Utilisation

**Commenter pour :**
- Logique complexe
- Algorithmes non évidents
- Sections de code importantes

**Format :**
```php
// Brief comment explaining the next block
/* Multi-line comments for complex logic */
```

---

##  Gestion des Erreurs

### PHP

**Structure obligatoire :**
```php
try {
    // Code principal
    // Réponse de succès
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Retourne erreur générique au client
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    // Gestion des autres erreurs
}
```

**Principes :**
- Logger toutes les erreurs avec `error_log()`
- Ne jamais exposer les détails techniques au client
- Retourner des messages d'erreur clairs et génériques
- Utiliser les codes HTTP appropriés

### JavaScript

**Gestion obligatoire :**
- try-catch pour le parsing JSON
- Vérification du status HTTP
- Gestion de `xhr.onerror`
- Gestion de `xhr.ontimeout`
- Affichage de notifications utilisateur

---

## Sécurité

### Authentification

**Obligatoire sur chaque action protégée :**
- `checkAuth()` : Vérifie si utilisateur connecté
- `checkAdminOrRespo()` : Vérifie le rôle
- `checkAdmin()` : Admin uniquement

### Validation des Entrées

**Toujours :**
- Valider le type de données
- Sanitizer les noms de fichiers
- Valider les emails et téléphones


### Fichiers Uploadés

**Vérifications obligatoires :**
- Type MIME autorisé
- Taille maximale
- Extension valide
- Nom de fichier sanitizé

---


### Frontend

**Bonnes pratiques :**
- Debounce sur les recherches (500ms)
- Cache pour données peu changeantes
- Pagination des listes longues

---

##  Versioning Git

### Messages de Commit (Anglais)

**Format :**
```
type: description courte
```

**Types :**
- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `test:` Tests

 **Exemples :**
```
feat: add terrain image upload
fix: correct price calculation
```

---

## Format des Réponses JSON

### Structure Standard

**Succès :**
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { ... }
}
```

**Erreur :**
```json
{
    "success": false,
    "message": "Error description"
}
```

### Codes HTTP

| Code | Utilisation |
|------|-------------|
| 200 | Succès GET/PUT/PATCH |
| 201 | Ressource créée (POST) |
| 204 | Succès DELETE |
| 400 | Données invalides |
| 401 | Non authentifié |
| 403 | Permission refusée |
| 404 | Ressource introuvable |
| 409 | Conflit (doublon) |
| 500 | Erreur serveur |

---

## Récapitulatif Exécutif

### Règles Essentielles

| Aspect | Convention | Exemple |
|--------|------------|---------|
| Fichiers | Anglais, lowercase-hyphen | `add_terrain.php` |
| Variables | Anglais, camelCase | `$currentTerrainId` |
| Fonctions | Anglais, camelCase, verbe | `processUpload()` |
| Constantes | Anglais, SCREAMING_SNAKE | `MAX_FILE_SIZE` |
| Commentaires | Anglais obligatoire | `// Upload image` |
| Tables DB | Français, snake_case, singulier | `terrain` |
| Colonnes DB | Français, snake_case | `prix_heure` |
| Commits | Anglais, format type: message | `feat: add feature` |


---

### Catégories Métier

**Catégories de terrain :**
- Grand Terrain (terrain complet)
- Terrain Moyen (terrain moyen)
- Mini Foot (petit terrain 5v5)

**Types de surface :**
- Gazon naturel (natural grass)
- Gazon synthétique (synthetic turf)
- Terre battue (clay surface)

**Statuts de disponibilité :**
- disponible (available)
- indisponible (unavailable)
- maintenance (under maintenance)

**Rôles utilisateur :**
- admin (administrateur système)
- responsable (gestionnaire de terrain)
- joueur (player)

---

## Conclusion

*Ce document constitue la référence officielle pour tous les développements sur le projet TerrainBook. Son respect est obligatoire pour garantir la qualité et la cohérence du code produit.*