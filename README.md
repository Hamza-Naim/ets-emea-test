# ETS EMEA - Réservation de sessions de tests de langues

Application web full-stack permettant aux utilisateurs de s'authentifier, consulter les sessions de tests de langues disponibles, réserver une session et gérer leurs réservations.

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Symfony 7.4 (PHP 8.3) |
| Frontend | Next.js 15 (TypeScript, React 19) |
| Base de données | MongoDB 7 |
| Authentification | JWT (LexikJWTAuthenticationBundle) |
| Serveur web | Nginx 1.27 |
| Conteneurisation | Docker + Docker Compose |
| Style | Tailwind CSS 4 |
| Tests backend | PHPUnit 11 |
| Tests frontend | Jest + React Testing Library |

## Prérequis

- **Docker Desktop** ([télécharger](https://www.docker.com/products/docker-desktop/))
- 4 Go de RAM minimum
- Ports libres : `3000`, `8000`, `27017`

Outil recommandé pour visualiser la base : [MongoDB Compass](https://www.mongodb.com/products/tools/compass).

## Installation

### 1. Cloner le projet

\`\`\`bash
git clone <url-du-repo>
cd ets-emea-test
\`\`\`

### 2. Créer le fichier `.env` à la racine

Copier `.env.example` en `.env` et garder les valeurs par défaut :

\`\`\`env
MONGO_ROOT_USER=root
MONGO_ROOT_PASSWORD=pwd_test_ets
MONGO_DB_NAME=ets_test
\`\`\`

### 3. Construire et démarrer la stack

\`\`\`bash
docker compose up -d --build
\`\`\`

La première construction prend 5 à 10 minutes (téléchargement des images et compilation de l'extension PHP MongoDB).

### 4. Installer les dépendances

\`\`\`bash
docker compose exec api composer install
docker compose exec front npm install
\`\`\`

### 5. Générer les clés JWT

\`\`\`bash
docker compose exec api php bin/console lexik:jwt:generate-keypair
\`\`\`

Passphrase demandée : `ets_test_passphrase`

### 6. Sur Windows : corriger les permissions

\`\`\`bash
docker compose exec --user root api chmod -R 777 var vendor
\`\`\`

### 7. Pré-remplir la base avec des sessions de langue

\`\`\`bash
docker compose exec api php bin/console app:seed-sessions
\`\`\`

15 sessions de tests de langues sont créées.

### 8. Accéder à l'application

| Service | URL |
|---------|-----|
| Frontend | http://localhost:3000 |
| API | http://localhost:8000 |
| MongoDB | mongodb://localhost:27017 |

Connexion Compass : `mongodb://root:pwd_test_ets@localhost:27017/?authSource=admin`

## Premier compte utilisateur

Aucun compte par défaut. Aller sur http://localhost:3000/register pour créer un compte.

## Tests

### Backend (17 tests PHPUnit)

\`\`\`bash
docker compose exec api php bin/phpunit
\`\`\`

Couvre : authentification JWT, CRUD utilisateurs, sessions de tests avec pagination, réservations, règles métier (anti-doublon, gestion des places, ownership).

### Frontend (10 tests Jest)

\`\`\`bash
docker compose exec front npm test
\`\`\`

Couvre : composant ConfirmModal, client API avec intercepteurs JWT.

## Endpoints de l'API

### Authentification (public)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/register` | Créer un compte |
| POST | `/api/login` | Se connecter (renvoie un JWT) |

### Profil utilisateur (authentifié)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/me` | Récupérer mes informations |
| PUT | `/api/me` | Modifier nom et email |

### Sessions de tests (authentifié)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/sessions?page=1&limit=10` | Liste paginée |
| GET | `/api/sessions/{id}` | Détail d'une session |
| POST | `/api/sessions` | Créer une session |
| PUT | `/api/sessions/{id}` | Modifier une session |
| DELETE | `/api/sessions/{id}` | Supprimer une session |

### Réservations (authentifié)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/reservations` | Mes réservations |
| POST | `/api/reservations` | Réserver une session avec `{"sessionId": "..."}` |
| DELETE | `/api/reservations/{id}` | Annuler une réservation |

### Exemple d'utilisation

\`\`\`bash
# Inscription
curl -X POST http://localhost:8000/api/register \\
  -H "Content-Type: application/json" \\
  -d '{"email":"alice@example.com","name":"Alice","password":"password123"}'

# Connexion
curl -X POST http://localhost:8000/api/login \\
  -H "Content-Type: application/json" \\
  -d '{"email":"alice@example.com","password":"password123"}'

# Appel authentifié
curl http://localhost:8000/api/sessions -H "Authorization: Bearer VOTRE_TOKEN"
\`\`\`

## Modèle de données

### Collection `users`

\`\`\`json
{
  "_id": "ObjectId",
  "email": "string (unique)",
  "name": "string",
  "password": "string (bcrypt)",
  "roles": ["ROLE_USER"],
  "createdAt": "Date"
}
\`\`\`

### Collection `sessions`

\`\`\`json
{
  "_id": "ObjectId",
  "language": "string",
  "date": "Date",
  "time": "string (HH:MM)",
  "location": "string",
  "totalSeats": "number",
  "availableSeats": "number"
}
\`\`\`

### Collection `reservations`

\`\`\`json
{
  "_id": "ObjectId",
  "user": "Reference -> users",
  "session": "Reference -> sessions",
  "reservedAt": "Date"
}
\`\`\`

Index unique sur `(user, session)` pour empêcher les doublons.

## Architecture

\`\`\`
Navigateur
   |
   v   http://localhost:3000
+----------+
| Next.js  |  Frontend (port 3000)
+----+-----+
     |  axios + JWT
     v   http://localhost:8000
+----------+
|  Nginx   |  Reverse proxy (port 8000)
+----+-----+
     |  FastCGI
     v
+----------+
| Symfony  |  API REST (PHP-FPM port 9000 interne)
+----+-----+
     |  Doctrine MongoDB ODM
     v
+----------+
| MongoDB  |  Base de données (port 27017)
+----------+
\`\`\`

## Structure du projet

\`\`\`
ets-emea-test/
├── docker-compose.yml          Orchestration des services
├── .env                        Variables d'environnement (à créer)
├── .env.example                Modèle de configuration
├── README.md
│
├── docker/
│   └── nginx/
│       └── default.conf        Configuration Nginx
│
├── api/                        Backend Symfony
│   ├── Dockerfile
│   ├── composer.json
│   ├── phpunit.dist.xml
│   ├── config/
│   │   ├── jwt/                Clés RSA (générées au setup)
│   │   └── packages/           Configuration des bundles
│   ├── src/
│   │   ├── Document/           Entités MongoDB
│   │   ├── Controller/         Controllers REST
│   │   └── Command/            Commandes CLI
│   ├── tests/                  Tests PHPUnit
│   └── public/index.php
│
└── front/                      Frontend Next.js
    ├── Dockerfile
    ├── package.json
    ├── jest.config.js
    └── src/
        ├── app/                App Router
        │   ├── login/
        │   ├── register/
        │   ├── sessions/
        │   ├── reservations/
        │   ├── account/
        │   └── layout.tsx
        ├── components/         Composants React réutilisables
        ├── context/            AuthContext
        ├── lib/                Client API et helpers
        └── __tests__/          Tests Jest
\`\`\`

## Fonctionnalités

- Authentification JWT (inscription, connexion, protection des routes)
- Gestion du compte utilisateur (modifier nom et email)
- Liste paginée des sessions avec affichage des places restantes
- Réservation avec décrémentation automatique des places
- Liste des réservations personnelles
- Annulation d'une réservation avec réincrémentation des places
- Modal de confirmation pour les actions destructives
- Design responsive avec Tailwind CSS

### Règles métier

- Un utilisateur ne peut pas réserver deux fois la même session
- La réservation est impossible si la session est complète
- Seul le propriétaire peut annuler sa réservation
- Les places sont décrémentées à la réservation et réincrémentées à l'annulation
- Validation des entrées avec Symfony Validator

## Résolution de problèmes

### Erreur "Your hydrator directory must be writable" (Windows)

\`\`\`bash
docker compose exec --user root api chmod -R 777 var vendor
\`\`\`

### Erreur JWT

\`\`\`bash
docker compose exec --user root api rm -f config/jwt/*.pem
docker compose exec api php bin/console lexik:jwt:generate-keypair
\`\`\`

### Erreur CORS

\`\`\`bash
docker compose exec --user root api rm -rf var/cache
docker compose restart api
\`\`\`

### Port déjà utilisé

Modifier les mappings de ports dans `docker-compose.yml`.

## Commandes utiles

\`\`\`bash
docker compose logs -f api          # Logs API en direct
docker compose logs -f front        # Logs frontend en direct
docker compose exec api php bin/console debug:router  # Lister les routes
docker compose exec api php bin/console cache:clear   # Vider le cache
docker compose down                 # Arrêter tous les services
docker compose down -v              # Arrêter et supprimer les volumes
\`\`\`

## Sécurité

- Mots de passe hashés avec bcrypt
- JWT RS256 (signature RSA asymétrique)
- Tokens avec TTL 1h
- CORS restreint aux origines configurées
- Validation des entrées avec Symfony Validator
- Index unique MongoDB sur `(user, session)`

## Licence

Projet réalisé dans le cadre d'un test technique pour ETS Global EMEA.