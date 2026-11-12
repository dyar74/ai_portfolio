# Yaroslav Dupliak — AI Automation Engineer

Multilingual (EN/UK) business-card website with **live, working n8n demos**.
The site is a portfolio and a reusable template library.

## Architecture

```mermaid
graph LR
    Browser[Browser] -->|POST /api/demo/*| BFF[Symfony BFF]
    Browser -->|GET /en /uk| BFF
    BFF -->|POST /webhook/*| N8N[n8n Instance]
    BFF -->|async| Redis[(Redis)]
    BFF -->|ORM| MySQL[(MySQL 8.4)]
    Redis -->|messenger| Worker[Messenger Worker]
    Worker -->|consume| Redis
    Worker -->|persist| MySQL
    BFF -->|cache| Redis
    BFF -->|health| N8N
```

**Key principle:** The browser NEVER calls n8n directly. All n8n traffic goes through the Symfony BFF, which handles:
- Rate limiting (5 req/min per IP per demo, Redis-backed)
- Validation (Symfony Validator + SSRF guard)
- HMAC signing + webhook secret
- Error mapping (timeout → 504, server error → 502, validation → 422)
- Async demo-run recording (Messenger + Redis)

## Quick Start

### Prerequisites
- Docker + Docker Compose
- Make
- Python 3 (for `make init` — generates secrets + DSNs)

### Run the stack

```bash
# 1. Create the root .env with random secrets (single source of truth for dev + prod)
cd portfolio-docker
make init

# 2. Build and start the dev stack (MySQL 8.4, Redis 7, PHP-FPM, nginx, messenger, Vite)
make up

# 3. Wait for healthy
make ps
make logs

# 4. Visit
#    http://localhost:8080/en
#    http://localhost:8080/uk
```

### Import the n8n workflows

1. Open your n8n instance: `https://n8n.novotek.com.ua`
2. Go to **Workflows → Import from file**
3. Import each JSON file from `n8n/workflows/`:
   - `contact-to-telegram.json`
   - `ai-translate-en-uk.json`
   - `url-summarizer.json`
   - `lead-scoring.json`
4. For each workflow, create the required credentials:
   - **LLM provider** (Google Gemini or OpenAI) — used by all 4 workflows
   - **Telegram bot** — used by `contact-to-telegram`
   - **SMTP** — used by `contact-to-telegram` (auto-reply email)
5. **Activate** each workflow
6. Copy the webhook URLs and verify they match the paths in `n8n/workflows/*.json`

### Environment variables

| Variable | Description | Example |
|---|---|---|
| `APP_ENV` | Symfony environment (`dev`/`prod`/`test`) | `dev` |
| `APP_SECRET` | Symfony secret (32+ hex chars) | `openssl rand -hex 32` |
| `DATABASE_URL` | MySQL DSN (derived from parts) | `mysql://user:pass@mysql:3306/db?serverVersion=8.4&charset=utf8mb4` |
| `REDIS_URL` | Redis DSN (derived from parts) | `redis://:pass@redis:6379` |
| `MESSENGER_TRANSPORT_DSN` | Messenger transport DSN (derived) | `redis://:pass@redis:6379/messages` |
| `MAILER_DSN` | SMTP DSN (dev: mailpit) | `smtp://mailpit:1025` |
| `N8N_BASE_URL` | n8n instance URL | `https://n8n.novotek.com.ua` |
| `N8N_WEBHOOK_SECRET` | Webhook secret (shared with n8n) | `openssl rand -hex 32` |
| `N8N_HMAC_KEY` | HMAC-SHA256 key for signing | `openssl rand -hex 32` |
| `N8N_API_KEY` | n8n Public API key (optional) | `xapiv1-...` |
| `TURNSTILE_SECRET` | Cloudflare Turnstile secret (optional) | `0x...` |
| `CONTACT_TO_EMAIL` | Email for lead notifications | `info@novotek.com.ua` |
| `TRUSTED_PROXIES` | Comma-separated trusted proxy IPs (empty by default) | `172.16.0.0/12` |

> **Single env file:** `make init` creates the repo-root `.env` (git-ignored) from `.env.example`.
> DSNs (`DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN`) are **derived** from the
> credential parts (`MYSQL_USER`, `MYSQL_PASSWORD`, `REDIS_PASSWORD`) by `envgen.py`,
> so they always match. In prod, `docker-compose.prod.yml` re-derives the same DSNs.

## Development

### Make targets

```bash
make help          # Show all targets
make up            # Build and start the dev stack
make down          # Stop the dev stack
make restart       # Restart php + messenger
make build         # Build dev images
make logs          # Tail logs
make ps            # Show containers
make sh            # Shell in the php container
make db            # MySQL client
make redis         # Redis CLI
make install       # composer install
make migrate       # Run Doctrine migrations
make migration     # Generate a migration from entity changes
make test          # PHPUnit
make stan          # PHPStan
make lint          # Frontend lint
make fresh         # Wipe volumes and rebuild from scratch
make backup        # Dump MySQL to backup-DATE.sql.gz

# Production
make prod-build    # Build production images
make prod-up       # Start production stack
make prod-migrate  # Run migrations in production
make prod-logs     # Tail production logs
make prod-down     # Stop production stack
make deploy        # One-shot deploy (pull + build + migrate + up + health check)
```

### Backend (PHP)

```bash
# Inside the php container (or locally with PHP 8.3 + extensions)
composer install
vendor/bin/phpstan analyse          # Static analysis (level 8)
php bin/phpunit                     # Tests
php bin/console doctrine:migrations:migrate  # Migrations
php bin/console messenger:consume async    # Messenger worker (manual)
```

### Frontend (Vue 3 + TypeScript + Vite)

```bash
cd frontend
npm ci
npm run dev        # Vite dev server on :5173
npm run lint       # ESLint
npm run typecheck  # vue-tsc
npm run test       # Vitest
npm run build      # Vite build → ../public/build
```

**i18n:** The frontend uses `vue-i18n` with `en.json` and `uk.json` locale files.
A custom Vite plugin (`vite.i18n.ts`) pre-compiles locale messages at build time
so that named interpolation (`{variable}`) works in the CSP-safe, runtime-only build
(no `new Function()`). The `mod.default` pattern in `i18n/index.ts` handles Rollup's
named→default export collapsing.

**Mobile:** The header uses a hamburger menu (no horizontal scroll). All section
headings and sub-headers are centered. Workflow gallery cards and graph nodes
are centered in their containers.

### API endpoints

| Endpoint | Method | Description |
|---|---|---|
| `/api/health` | GET | n8n health status (cached 30s) |
| `/api/stats` | GET | Demo run aggregates (cached 5min) |
| `/api/n8n/last-executions` | GET | Last 5 n8n executions (cached 60s) |
| `/api/demo/contact-to-telegram` | POST | Lead intake → Telegram + email |
| `/api/demo/ai-translate-en-uk` | POST | AI translation EN ↔ UK |
| `/api/demo/url-summarizer` | POST | URL summarization (5 bullets + TL;DR) |
| `/api/demo/lead-scoring` | POST | Lead scoring (0-100 + reasons) |
| `/robots.txt` | GET | robots.txt |
| `/sitemap.xml` | GET | sitemap.xml (en + uk) |

### Error codes

| HTTP | Meaning |
|---|---|
| `404` | Unknown demo slug |
| `415` | Wrong Content-Type (must be `application/json`) |
| `422` | Validation failed (body includes `violations[]`) |
| `429` | Rate limited (header `Retry-After` + body `retryAfter`) |
| `502` | n8n server error (after retry) |
| `504` | n8n timeout (20s) |

## Troubleshooting

### n8n badge shows "down"
1. Check `N8N_BASE_URL` is correct in `.env` (repo root)
2. `curl -v https://n8n.novotek.com.ua/healthz` — should return 200
3. Check n8n is running and the workflows are activated
4. Check firewall/security groups allow outbound HTTPS from the Docker network

### Redis connection refused
1. Verify `REDIS_URL` in `.env` (repo root) matches the Redis container password
2. `make redis` → `PING` → should return `PONG`
3. Check Redis is running: `make ps`

### Vite HMR not working in Docker
1. The Vite dev server runs on the host at `localhost:5173`
2. The `vite_assets()` Twig function emits `http://localhost:5173` in dev
3. If you're behind a reverse proxy, update `VITE_DEV_HOST` in `frontend/vite.config.ts`

### Rate limit 429
1. The limit is 5 req/min per client IP per demo (Redis-backed)
2. Wait 60s or flush the Redis limiter state:
   ```bash
   make redis
   > FLUSHDB
   > EXIT
   ```

### Migrations fail
1. `make db` → check MySQL is running
2. `make migrate` → run migrations
3. If stuck, `make fresh` wipes volumes and rebuilds from scratch

### PHPStan errors
1. `make stan` → see the errors
2. Common: missing `@param`/`@return` annotations, type mismatches
3. Fix the code, do NOT suppress with `@phpstan-ignore`

### Tests fail
1. `make test` → see the failures
2. Common: MockHttpClient not matching the expected request
3. Check the request URL, headers, and body in the test

### i18n shows raw keys (e.g. `demos.heading`)
1. Ensure `vite.i18n.ts` plugin is included in `vite.config.ts`
2. Ensure `i18n/index.ts` uses `mod.default` (Rollup collapses named exports to default)
3. Rebuild: `npm run build` → verify `_i18n-precompiled_*.js` chunks exist in the build output
4. Hard-refresh the browser (Ctrl+Shift+R)

### Orphan container warning
```
WARN[0000] Found orphan containers (portfolio-node-1, portfolio-mailpit-1)
```
All `up`/`down` Make targets now include `--remove-orphans`. If you see this warning,
you're using a stale Makefile. Run `git pull` and retry.

## Auto-Deploy (GitHub Actions → prod VM)

Pushing to `main` automatically deploys to the production VM (after CI passes).

### One-time setup

1. **Create an SSH key** (on your laptop):
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/deploy_prod -N ""
   cat ~/.ssh/deploy_prod.pub
   ```

2. **Add the public key to the VM** (`/home/deploy/.ssh/authorized_keys`):
   ```bash
   ssh deploy@YOUR_VM_IP
   mkdir -p ~/.ssh && chmod 700 ~/.ssh
   echo "ssh-ed25519 AAAA... deploy@laptop" >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Add GitHub secrets** (repo → Settings → Secrets and variables → Actions):

   | Secret | Value |
   |--------|-------|
   | `DEPLOY_HOST` | `YOUR_VM_IP` |
   | `DEPLOY_USER` | `deploy` |
   | `DEPLOY_SSH_KEY` | contents of `~/.ssh/deploy_prod` (private key) |
   | `HEALTH_URL` | `https://yourdomain.com/api/health` (optional) |

4. **Create the `production` environment** (repo → Settings → Environments → New environment → `production`).

### How it works

```
push to main
  → CI (backend + frontend + docker smoke)
  → deploy job:
      SSH into VM
      → bash portfolio-docker/deploy.sh
          1. git pull
          2. docker compose build
          3. doctrine:migrations:migrate
          4. docker compose up -d --wait
          5. health check
```

### Manual deploy (from the VM)

```bash
cd /opt/portfolio
bash portfolio-docker/deploy.sh
# or:
cd portfolio-docker && make deploy
```

### Rollback

```bash
cd /opt/portfolio
git log --oneline -5
git checkout <previous-commit>
cd portfolio-docker && make deploy
```

## Project structure

```
├── src/                    # Symfony backend
│   ├── Controller/         # API controllers (thin orchestrators)
│   ├── Service/            # Business logic (N8nClient, DemoExecutor, etc.)
│   ├── Dto/                # Request DTOs with validation
│   ├── Entity/             # Doctrine entities (Lead, DemoRun)
│   ├── Repository/         # Doctrine repositories
│   ├── Message/            # Messenger messages
│   ├── MessageHandler/     # Message handlers
│   ├── Validator/          # Custom validators (SafeUrl)
│   ├── EventListener/      # Event subscribers (CSP)
│   ├── Twig/               # Twig extensions (ViteExtension)
│   ├── Command/            # Console commands (retention purge)
│   └── Schedule.php        # Symfony Scheduler tasks
├── config/
│   ├── packages/           # Symfony bundle configs
│   ├── routes/             # Route configs
│   └── services.yaml       # Service wiring
├── frontend/               # Vue 3 + TypeScript + Vite
│   ├── src/
│   │   ├── components/     # Vue components (28)
│   │   │   ├── layout/     # Header (hamburger menu), Footer
│   │   │   ├── sections/   # Hero, Services, Demos, Gallery, Process, About, Contact
│   │   │   ├── demos/      # ContactDemo, TranslateDemo, SummarizerDemo, LeadScoringDemo
│   │   │   └── ...         # SectionHeading, HealthBadge, LiveStats, Tabs, etc.
│   │   ├── composables/    # Vue composables (useDemo, etc.)
│   │   ├── stores/         # Pinia stores (theme, locale)
│   │   ├── views/          # Page views
│   │   ├── locales/        # i18n JSON (en.json, uk.json)
│   │   ├── data/           # Static data (workflows.ts)
│   │   └── utils/          # Utilities (api.ts)
│   ├── __tests__/          # Vitest tests (14)
│   ├── vite.i18n.ts        # Vite plugin: pre-compile locale messages
│   └── vite.config.ts      # Vite config
├── n8n/workflows/          # 4 importable n8n workflow JSONs
├── docker/                 # Dockerfiles + nginx config (READ-ONLY contract)
│   ├── php/                # PHP-FPM Dockerfile
│   ├── nginx/              # Nginx config
│   └── mysql/              # MySQL config
├── portfolio-docker/       # Makefile + deploy.sh + envgen.py
├── docs/                   # Deployment guides
│   └── deploy-oracle-cloudflare.md
├── tests/                  # PHPUnit tests
├── .github/workflows/      # CI (ci.yml) + Auto-deploy (deploy.yml)
├── LICENSE                 # Apache License 2.0
├── SKILL.md                # Development principles (ub-mode)
├── memory.md               # Project decision log
└── public/images/          # Profile photo (profile.jpg)
```

## License

Apache License 2.0 — see [LICENSE](LICENSE) for details.

© Yaroslav Dupliak

## AI-Assisted

This project was created with the assistance of AI tools.
