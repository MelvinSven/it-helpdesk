#!/usr/bin/env bash
#
# Production deploy / update script for the IT Helpdesk app.
# Run from the application root (the dir containing artisan) on the server:
#
#     ./deploy.sh
#
# Idempotent: safe to re-run. Assumes .env is already configured on the server
# (never commit it). For a brand-new server, see the "First-time setup" note
# at the bottom of this file.

set -euo pipefail

echo "==> IT Helpdesk deploy starting"

# 1. Maintenance mode (best-effort; ignore if already down or app not bootable)
php artisan down --render="errors::503" --retry=15 || true
trap 'php artisan up || true' EXIT

# 2. Pull latest code
git pull --ff-only

# 3. PHP dependencies (production: no dev packages, optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4. Front-end build (Inertia/React assets)
npm ci
npm run build

# 5. Database migrations (new: proyek column on users; jobs table for queue)
php artisan migrate --force

# 6. Storage symlink (procurement scans, item/borrow images on public disk)
php artisan storage:link || true

# 7. Rebuild framework caches (config/route/view + events)
#    clear first so removed keys like services.support don't linger
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Restart queue workers so they pick up new code.
#    All ticket notifications implement ShouldQueue — a worker MUST be running
#    (via supervisor/systemd) or emails never send. This signals a graceful
#    restart; the process manager respawns the worker.
php artisan queue:restart

echo "==> Deploy complete"
# EXIT trap runs `php artisan up`

# ---------------------------------------------------------------------------
# First-time setup (run manually, once):
#   cp .env.example .env && edit it:
#     - APP_KEY:  php artisan key:generate
#     - MAIL_MAILER=gmail + GMAIL_CLIENT_ID / GMAIL_CLIENT_SECRET / GMAIL_REFRESH_TOKEN
#     - MAIL_FROM_ADDRESS / MAIL_FROM_NAME
#     - DB_* (or point to a real DB; SQLite: touch database/database.sqlite)
#     - remove SUPPORT_NOTIFICATION_EMAIL (config key was deleted)
#   Provision a persistent queue worker (see helpdesk-worker.service below).
#   Optional roster backfill (files are gitignored — copy to server manually):
#     php artisan db:seed --class=UserProyekSeeder --force
# ---------------------------------------------------------------------------
