#!/usr/bin/env bash
#
# deploy.sh — run on the prod VM (invoked by the GitHub Actions deploy workflow,
# or manually via:  bash portfolio-docker/deploy.sh)
#
# What it does (idempotent, safe to re-run):
#   1. git pull (only if there is something new)
#   2. docker compose build (rebuilds php + nginx_prod images)
#   3. docker compose run --rm php bin/console doctrine:migrations:migrate
#   4. docker compose up -d --wait (recreate changed containers, keep volumes)
#   5. health check
#
# Env file: uses the repo-root .env (auto-loaded by Compose). If it does not
# exist, this script REFUSES to run (do not generate random secrets on the fly
# for a live database).
#
set -euo pipefail

# --- Resolve paths (run from anywhere) ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

echo "==> Portfolio deploy — $(date -u +%FT%TZ) — $(hostname)"

# --- 0. Pre-flight ---
command -v docker >/dev/null 2>&1 || { echo "ERROR: docker not found" >&2; exit 1; }
command -v git    >/dev/null 2>&1 || { echo "ERROR: git not found" >&2; exit 1; }

if [ ! -f .env ]; then
  echo "ERROR: repo-root .env not found." >&2
  echo "  Create it once on the VM:  cd portfolio-docker && make init && nano ../.env" >&2
  echo "  (Do NOT auto-generate secrets for a live database.)" >&2
  exit 1
fi

# --- 1. git pull (only if behind) ---
echo "==> git status"
git status --short || true
BEHIND=$(git rev-list --count HEAD..@{u} 2>/dev/null || echo 0)
if [ "$BEHIND" -gt 0 ]; then
  echo "==> git pull ($BEHIND commit(s) behind)"
  git pull --ff-only
else
  echo "==> already up to date (no pull needed)"
fi

# --- 2. build ---
echo "==> docker compose build"
cd "$SCRIPT_DIR"
make prod-build

# --- 3. migrate ---
echo "==> docker compose run php doctrine:migrations:migrate"
make prod-migrate

# --- 4. up (recreate changed containers; volumes persist) ---
echo "==> docker compose up -d --wait"
make prod-up

# --- 5. health check (local) ---
echo "==> health check"
PORT="${HTTP_PORT:-8080}"
for i in 1 2 3 4 5 6 7 8 9 10; do
  if curl -sf --max-time 5 "http://127.0.0.1:${PORT}/api/health" >/dev/null 2>&1; then
    echo "    OK: /api/health responds"
    curl -s "http://127.0.0.1:${PORT}/api/health" | head -c 400; echo
    break
  fi
  if [ "$i" -eq 10 ]; then
    echo "WARN: /api/health not responding after 50s — check logs:" >&2
    make prod-logs 2>&1 | tail -30 || true
    exit 1
  fi
  sleep 5
done

echo "==> deploy complete — $(date -u +%FT%TZ)"
