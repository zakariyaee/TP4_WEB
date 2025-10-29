# 🧩 Convention de Codage – Résumé

## 🎯 Objectif
Garantir un **code clair, cohérent et professionnel** entre tous les membres du projet **TerrainBook**.

Objectifs :
- **Cohérence** du code  
- **Sécurité** et **maintenabilité**  
- **Qualité** et **performance**  
- **Collaboration efficace**

---

## 🌍 Langue
| Élément | Langue | Justification |
|----------|--------|---------------|
| Fichiers / Dossiers | **Anglais** | Standard international |
| Variables / Fonctions | **Anglais** | Convention universelle |
| Commentaires / Docs | **Anglais** | Partage technique |
| Base de Données | **Français** | Cohérence métier locale |

---

## 🏗️ Structure du Projet
```
TP4_WEB/
├── actions/         # Logique backend (PHP)
├── assets/          # Ressources (images, JS)
├── config/          # Configurations
├── includes/        # Composants réutilisables
└── views/           # Interfaces (admin, auth, player)
```

---

## 📘 Nommage
| Élément | Style | Exemple |
|----------|--------|----------|
| Dossiers | lowercase-hyphen | `admin-respo/` |
| Fichiers PHP | action_entity.php | `add_terrain.php` |
| Variables | camelCase | `$currentTerrainId` |
| Constantes | SCREAMING_SNAKE_CASE | `MAX_FILE_SIZE` |
| Fonctions | camelCase + verbe | `processUpload()` |

---

## 🧱 PHP
**Structure type :**
1. Includes  
2. Authentification  
3. Validation HTTP  
4. Logique métier  
5. Gestion des erreurs  
6. Réponse JSON  

**Sécurité :**
- Prepared statements obligatoires  
- Validation des entrées  
- Pas d’injection SQL / XSS  
- Ne pas afficher les erreurs brutes

---

## ⚙️ JavaScript
- Organisation : variables → DOM → listeners → AJAX  
- Nommer en anglais (`camelCase`)  
- Constantes en majuscules  
- Gestion complète des erreurs AJAX  
- Commentaires en anglais

---

## 🗄️ Base de Données
- Tables : Français, snake_case, singulier → `terrain`, `reservation`
- Colonnes : Français, snake_case → `prix_heure`, `date_debut`
- Clés : `id_terrain`, `id_utilisateur`, `id_responsable`
- Types : `INT`, `VARCHAR`, `DECIMAL`, `DATE`, `TIME`

---

## 💬 Documentation & Commentaires
En **anglais uniquement**, pour :
- Logique complexe  
- Algorithmes  
- TODO / FIXME  

Format :
```js
// Short comment
/* Long explanation */
```

---

## 🚨 Gestion des Erreurs
**PHP :**
```php
try { ... } 
catch (PDOException $e) { ... }
```
- Logger sans afficher  
- Message JSON clair  

**JS :**
- try-catch sur JSON  
- Vérification HTTP status  
- Message utilisateur clair

---

## 🔐 Sécurité
- Auth obligatoire (`checkAuth()`, `checkAdminOrRespo()`)  
- Validation stricte des entrées  
- Uploads : taille, type, nom sécurisés  

---

## ⚡ Performances
**SQL :**
- JOIN optimisés, LIMIT, index  

**Frontend :**
- debounce (500ms)  
- lazy loading  
- pagination

---

## 🌳 Versioning Git
**Commits :** `type: description`
- feat → nouvelle fonctionnalité  
- fix → correction  
- docs → documentation  
- refactor → amélioration du code  

**Branches :** `type/description`  
> Ex : `feature/terrain-management`

---

## 📦 Format JSON
**Succès :**
```json
{ "success": true, "message": "OK", "data": {} }
```
**Erreur :**
```json
{ "success": false, "message": "Error" }
```

---

## 📑 Récapitulatif
| Élément | Convention | Exemple |
|----------|-------------|----------|
| Fichiers | anglais, lowercase-hyphen | `add_terrain.php` |
| Variables | camelCase | `$currentTerrainId` |
| Constantes | SCREAMING_SNAKE_CASE | `MAX_FILE_SIZE` |
| Tables | français, snake_case | `terrain` |
| Colonnes | français, snake_case | `prix_heure` |
| Commits Git | type: description | `feat: add login feature` |

---

## 🧠 Règle d’Or
> **Code en anglais, données métier en français.**

---

## 🔧 Références
- PSR-1 / PSR-12 (PHP)  
- Airbnb JS Style Guide  
- REST API Best Practices  

**Outils :** PHPStan • ESLint • Git • PHPDoc • JSDoc
