# PLAN DE CONFIGURATION PRODUCTION - BOW Refonte

Date : 2026-02-11

---

## 1. FICHIERS .ENV A CREER

### 1.1 Root `.env` (Docker Compose)

```env
DOMAIN_NAME=ohadja.com
DB_USER=bow_user
DB_PASSWORD=<MOT_DE_PASSE_FORT_16_CHARS_MIN>
DB_NAME=bow_database
APP_KEY=<GENERER_AVEC_php_artisan_key:generate>
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=smtp
MAIL_HOST=<SMTP_SERVEUR>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USER>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ohadja.com
MAIL_FROM_NAME="Book of Work"
```

### 1.2 Backend `tavira-bow-api/.env`

```env
# --- Application ---
APP_NAME="Book of Work"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api-bow.ohadja.com
APP_KEY=<GENERER>

# --- Database PostgreSQL ---
DB_CONNECTION=pgsql
DB_HOST=database_bow
DB_PORT=5432
DB_DATABASE=bow_database
DB_USERNAME=bow_user
DB_PASSWORD=<MOT_DE_PASSE_FORT>

# --- Redis ---
REDIS_HOST=redis_bow
REDIS_PORT=6379
REDIS_PASSWORD=<MOT_DE_PASSE_REDIS>
REDIS_CLIENT=predis
REDIS_PREFIX=tavira_bow_
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=true

# --- Logging ---
LOG_CHANNEL=stack
LOG_LEVEL=error

# --- Mail SMTP ---
MAIL_MAILER=smtp
MAIL_HOST=<SMTP_SERVEUR>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USER>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ohadja.com
MAIL_FROM_NAME="Book of Work"

# --- Auth Sanctum ---
SANCTUM_STATEFUL_DOMAINS=bow.ohadja.com
FRONTEND_URL=https://bow.ohadja.com

# --- Horizon ---
HORIZON_PREFIX=tavira_bow_horizon:

# --- Sentry ---
SENTRY_LARAVEL_DSN=<DSN_SENTRY_BACKEND>
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false

# --- Storage (local par defaut, S3 optionnel) ---
FILESYSTEM_DISK=local
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=tavira-bow-prod
# AWS_ENDPOINT=http://minio:9000
# AWS_USE_PATH_STYLE_ENDPOINT=true

# --- Backup ---
BACKUP_NOTIFICATION_EMAIL=admin@ohadja.com
BACKUP_ARCHIVE_PASSWORD=<MOT_DE_PASSE_BACKUP>

# --- Currency ---
CURRENCY_DEFAULT=GBP
CURRENCY_EUR_TO_GBP=0.85
CURRENCY_USD_TO_GBP=0.79
```

### 1.3 Frontend `tavira-bow-frontend/.env.production`

```env
NEXT_PUBLIC_API_URL=https://api-bow.ohadja.com
NEXT_PUBLIC_APP_NAME="Book of Work"
NEXT_PUBLIC_APP_URL=https://bow.ohadja.com
NEXT_PUBLIC_SENTRY_DSN=<DSN_SENTRY_FRONTEND>
```

---

## 2. SECURITE - VERIFICATIONS OBLIGATOIRES

### 2.1 Variables critiques

| Variable | Valeur requise | Pourquoi |
|----------|---------------|----------|
| `APP_DEBUG` | `false` | JAMAIS true en prod |
| `APP_ENV` | `production` | Active les caches et optimisations |
| `LOG_LEVEL` | `error` | Pas de debug en prod |
| `SENTRY_SEND_DEFAULT_PII` | `false` | Conformite RGPD |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS uniquement |

### 2.2 Secrets a generer

| Secret | Commande / Methode | Min. longueur |
|--------|-------------------|---------------|
| `APP_KEY` | `php artisan key:generate` | 32 bytes base64 |
| `DB_PASSWORD` | Generateur aleatoire | 16 caracteres |
| `REDIS_PASSWORD` | Generateur aleatoire | 16 caracteres |
| `BACKUP_ARCHIVE_PASSWORD` | Generateur aleatoire | 16 caracteres |
| `MAIL_PASSWORD` | Fournisseur SMTP | - |

### 2.3 CORS - Retirer localhost

Fichier `tavira-bow-api/config/cors.php` : retirer `http://localhost:3000` des `allowed_origins` en production. Garder uniquement `https://bow.ohadja.com`.

### 2.4 Permissions fichiers

```bash
docker exec bow_api chmod -R 775 storage bootstrap/cache
docker exec bow_api chown -R www-data:www-data storage bootstrap/cache
```

---

## 3. INFRASTRUCTURE DOCKER

### 3.1 Services (docker-compose.yml)

| Service | Image | Port | Role |
|---------|-------|------|------|
| database | postgres:16-alpine | 5432 | PostgreSQL |
| redis | redis:7-alpine | 6379 | Cache/Queue/Session |
| api | php:8.3-fpm-alpine | 8000 | Laravel API |
| queue | (meme image api) | - | Queue worker |
| scheduler | (meme image api) | - | Cron tasks |
| frontend | node:20-alpine | 3000 | Next.js |
| webdb | webdb/app | 22071 | Admin DB (optionnel) |

### 3.2 Traefik (reverse proxy)

| Sous-domaine | Service | Port |
|-------------|---------|------|
| `bow.ohadja.com` | frontend | 3000 |
| `api-bow.ohadja.com` | api | 8000 |
| `webdb-bow.ohadja.com` | webdb | 22071 |

HTTPS/TLS gere par Traefik automatiquement (Let's Encrypt).

### 3.3 Volumes persistants a sauvegarder

| Chemin | Contenu |
|--------|---------|
| `./db/` | Donnees PostgreSQL |
| `./redis/` | Donnees Redis |
| `./tavira-bow-api/storage/` | Fichiers uploades, backups, logs |

---

## 4. TACHES PLANIFIEES (Scheduler)

| Commande | Frequence | Heure | Description |
|----------|-----------|-------|-------------|
| `bow:send-daily-summary` | Quotidien | 07:00 | Resume quotidien admins |
| `bow:send-task-reminders` | Quotidien | 08:00 | Rappels echeances taches |
| `bow:send-contract-alerts` | Quotidien | 09:00 | Alertes expiration contrats |
| `bow:recalculate-dashboard` | Horaire | - | Recalcul cache dashboard |
| `backup:run --only-db` | Quotidien | 02:00 | Backup BDD |
| `backup:run` | Hebdo | Dim 03:00 | Backup complet |
| `backup:clean` | Hebdo | Dim 04:00 | Nettoyage vieux backups |
| `backup:monitor` | Quotidien | 06:00 | Verification sante backups |
| `activitylog:clean` | Mensuel | 1er | Nettoyage audit trail |

Le container `scheduler` dans docker-compose.yml execute deja `php artisan schedule:run` en boucle.

---

## 5. HORIZON (Queue Monitoring)

### 5.1 Supervisors configures

| Supervisor | Workers | Timeout | Queues |
|-----------|---------|---------|--------|
| default | 10 | 60s | default |
| imports | 3 | 600s | imports |
| notifications | 5 | 30s | notifications |

### 5.2 Acces

- URL : `https://api-bow.ohadja.com/horizon`
- Local : tous les utilisateurs
- Production : admins uniquement (`$user->isAdmin()`)

### 5.3 A configurer

Remplacer le container `queue` par Horizon en prod (optionnel) :
```yaml
command: php artisan horizon
```

---

## 6. SENTRY (Error Tracking)

| Composant | Variable | Sample Rate |
|-----------|----------|-------------|
| Backend | `SENTRY_LARAVEL_DSN` | Errors: 100%, Traces: 10% |
| Frontend | `NEXT_PUBLIC_SENTRY_DSN` | Errors: 100%, Traces: 100% (a reduire) |

A faire : creer le projet sur sentry.io et recuperer les DSN.

---

## 7. COMMANDES DE DEPLOIEMENT

### 7.1 Premier deploiement

```bash
# 1. Configurer les 3 fichiers .env (root, backend, frontend)
# 2. Build et demarrage
docker-compose up -d --build

# 3. Generer APP_KEY
docker exec bow_api php artisan key:generate

# 4. Migrations
docker exec bow_api php artisan migrate --force

# 5. Seeders (donnees initiales)
docker exec bow_api php artisan db:seed --force

# 6. Storage link
docker exec bow_api php artisan storage:link

# 7. Cache de production
docker exec bow_api php artisan config:cache
docker exec bow_api php artisan route:cache
docker exec bow_api php artisan view:cache

# 8. Verifier
docker exec bow_api php artisan about
```

### 7.2 Mise a jour

```bash
git pull origin main
docker-compose up -d --build
docker exec bow_api php artisan migrate --force
docker exec bow_api php artisan config:clear && docker exec bow_api php artisan config:cache
docker exec bow_api php artisan route:clear && docker exec bow_api php artisan route:cache
docker exec bow_api php artisan view:clear && docker exec bow_api php artisan view:cache
docker restart bow_queue
```

---

## 8. MONITORING POST-DEPLOIEMENT

| Check | Methode | Frequence |
|-------|---------|-----------|
| API health | `curl https://api-bow.ohadja.com/api/health` | Continu |
| Frontend | `curl https://bow.ohadja.com` | Continu |
| Horizon | Dashboard web | Quotidien |
| Sentry | Dashboard web | Quotidien |
| Backups | Email notification | Quotidien |
| PostgreSQL | `pg_isready` (healthcheck Docker) | Continu |
| Redis | `redis-cli ping` (healthcheck Docker) | Continu |
| Logs | `docker logs bow_api` | En cas d'incident |

---

## 9. URLS PRODUCTION

| Service | URL |
|---------|-----|
| Frontend | https://bow.ohadja.com |
| API | https://api-bow.ohadja.com |
| Horizon | https://api-bow.ohadja.com/horizon |
| WebDB | https://webdb-bow.ohadja.com |
