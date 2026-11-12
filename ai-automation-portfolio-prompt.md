# Master Prompt: Bilingual Business-Card Site for an AI Automation Engineer
Symfony + Vue 3 + live n8n demos (your-n8n.example.com)

## How to use (not part of the prompt)
1. Put the contents of `portfolio-docker/` (Docker setup, delivered separately) into the repo root first. The prompt below assumes it exists and does NOT ask the AI to generate it.
2. Fill the `[PLACEHOLDERS]` in section 1.
3. Paste everything from "PROMPT START" to the end into your AI tool (Claude Code, Cursor, etc.).
4. Work phase by phase (section 11): say "do phase 1", review, then continue.

---

## ======== PROMPT START ========

# ROLE
You are a senior full-stack engineer (PHP/Symfony, Vue 3/TypeScript) and an n8n automation specialist. Build a production-ready, bilingual (EN/UK) business-card website for an AI Automation Engineer. The site must contain LIVE, working demos that run on my self-hosted n8n instance: https://your-n8n.example.com

Deliver complete, runnable code, not pseudo-code or summaries. Work in phases (section 11). After each phase, list the files created and the commands to run and verify.

# 1. OWNER DATA (config, never hardcoded in components)
- Name: Your Name
- Title EN: "AI Automation Engineer" / UK: "Інженер з AI-автоматизації"
- Email: [you@example.com] | Telegram: [@yourtelegram] | LinkedIn: [https://www.linkedin.com/in/yourprofile/] | GitHub: [https://github.com/youruser/]
- Location: Ukraine, remote-friendly
- Services (3-5): n8n workflow automation, LLM integrations (OpenAI/Anthropic), AI agents and RAG, API/CRM/Telegram integrations, process audits
- Store all content in `content/en.json` and `content/uk.json` (or vue-i18n message files), never in templates.

# 2. TECH STACK (fixed)
- Backend: PHP 8.3, Symfony 7.x with: framework-bundle, http-client, rate-limiter, validator, mailer, translation, monolog-bundle, messenger + redis-messenger, doctrine/orm + doctrine-bundle + doctrine-migrations-bundle, symfony/uid, scheduler. Use attributes for routing, DTOs with `#[MapRequestPayload]`, readonly services with autowiring.
- Frontend: Vue 3 (Composition API, `<script setup lang="ts">`), Vite, vue-router 4, vue-i18n 9, Pinia, Tailwind CSS. Optional: @vue-flow/core to render n8n workflow graphs from exported JSON.
- Data: MySQL 8.4, Redis 7.
- Tests/quality: PHPUnit (with `MockHttpClient` for n8n calls), Vitest + @vue/test-utils, PHPStan level 8, ESLint + Prettier, vue-tsc.

# 3. INFRASTRUCTURE CONTRACT (already provided, do NOT generate Docker/nginx files)
A Docker setup (`portfolio-docker/`) is already in the repo root. Your code must fit it exactly:

**Project layout**
- Symfony project in the repo root (`composer.json`, `composer.lock` and `symfony.lock` MUST be committed: the prod image build copies them).
- Vue app in `/frontend` (`package.json` and `package-lock.json` MUST be committed). Required npm scripts: `dev`, `build`, `lint`, `test`, `typecheck`.
- Tests run as `bin/phpunit`; static analysis as `vendor/bin/phpstan analyse`.

**Vite config (required)**
```ts
// frontend/vite.config.ts
export default defineConfig({
  plugins: [vue()],
  server: { host: true, port: 5173, strictPort: true, hmr: { host: 'localhost' } },
  build: { outDir: '../public/build', emptyOutDir: true, manifest: true,
           rollupOptions: { input: 'src/main.ts' } },
})
```

**Twig shell + assets**
- One Twig template (`templates/app.html.twig`) mounts the Vue app (`<div id="app">`).
- Implement a Twig function `vite_assets()`:
  - `APP_ENV=dev`: emit `<script type="module" src="http://localhost:5173/@vite/client">` and `http://localhost:5173/src/main.ts`.
  - `APP_ENV=prod`: read `public/build/.vite/manifest.json`, emit the hashed JS + CSS (+ modulepreload for imports), fail loudly if the manifest is missing.
- The catch-all route serves the shell for `/`, `/en/...`, `/uk/...`; `/api/*` is JSON only.

**Services (hostnames inside the Docker network)**
- `mysql:3306` (MySQL 8.4, utf8mb4), `redis:6379` (password-protected), `nginx` -> `php:9000`, `messenger` worker (consumes transport `async`), optional `scheduler` worker (consumes transport `scheduler_default`), `mailpit` (SMTP `mailpit:1025`, dev only), Vite dev server on `localhost:5173`.
- Dev site URL: http://localhost:8080. nginx is configured with `fastcgi_read_timeout 30s`, so the n8n client timeout must stay at or below 20s.

**Environment variables injected by Compose (read them via `%env()%`, never hardcode)**
`APP_ENV`, `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN`, `MAILER_DSN`, `N8N_BASE_URL`, `N8N_WEBHOOK_SECRET`, `N8N_HMAC_KEY`, `N8N_API_KEY`, `TURNSTILE_SECRET`, `CONTACT_TO_EMAIL`.
(`TELEGRAM_CHAT_ID` and the LLM/SMTP keys live inside n8n credentials, not in this app.)

**Behavior you must respect**
- In dev, the php container runs `doctrine:migrations:migrate` on start (`RUN_MIGRATIONS=1`): migrations must be idempotent, ordered, and committed. In prod, migrations run only as an explicit deploy step.
- The app runs as non-root in prod: write only to `var/`.
- Requests reach Symfony via nginx; if a reverse proxy is added in front, client IPs come from `X-Forwarded-For` after nginx `real_ip` is enabled. Expose `TRUSTED_PROXIES` as an env-configurable parameter, off by default.
- Security headers (except CSP and HSTS) are set by nginx. YOU set CSP in Symfony (an event subscriber): `default-src 'self'`; allow `http://localhost:5173` and `ws://localhost:5173` only when `APP_ENV=dev`; add Turnstile/Google Fonts/analytics origins only if those features are enabled.

# 4. ARCHITECTURE: Symfony as a secure BFF in front of n8n
The browser MUST NEVER call n8n directly. Flow:
`Vue -> POST /api/demo/{slug} (Symfony) -> POST https://your-n8n.example.com/webhook/{path} -> JSON -> Symfony normalizes -> Vue`

Implement:
- `N8nClient` service: Symfony HttpClient, base URI `N8N_BASE_URL`, timeout 20s, max 1 retry on 502/503/504, header `X-Webhook-Secret: %env(N8N_WEBHOOK_SECRET)%`, `X-Signature: sha256=<HMAC-SHA256 of the raw body with N8N_HMAC_KEY>`, and `X-Request-Id` (uuid, propagated to logs).
- `DemoController` with one action per demo plus a `DemoRegistry` mapping slug -> webhook path, request DTO, response DTO.
- Validation with Symfony Validator (max lengths, allowed locales, URL scheme allow-list for the summarizer).
- Rate limiting: `sliding_window` limiter, 5 req/min and 30 req/day per client IP per demo, Redis-backed, 429 with `Retry-After`.
- Honeypot field + optional Cloudflare Turnstile verification (feature-flagged by env).
- Error mapping: n8n timeout -> 504 `{error:"demo_timeout"}`, n8n 5xx -> 502, validation -> 422. Never leak n8n URLs, stack traces, or secrets. All `/api/*` responses carry `Cache-Control: no-store`.
- `GET /api/health`: pings `https://your-n8n.example.com/healthz` (cached 30s in Redis) and returns `{site:"ok", n8n:"ok|down", latencyMs}`. The footer shows a green/red "n8n live" badge.
- Optional `GET /api/n8n/last-executions`: uses the n8n Public API (`X-N8N-API-KEY`, server-side only) to return the last 5 executions of the demo workflows (id, workflow name, status, duration, startedAt, no payload data), cached 60s.
- CORS closed (same-origin). Enforce `Content-Type: application/json` and an Origin/Referer check on POSTs. CSRF tokens are not needed for this stateless JSON API.

# 5. DATA LAYER (MySQL + Redis)
**MySQL (Doctrine ORM + Migrations, `serverVersion=8.4`, utf8mb4):**
- `Lead`: id (UUIDv7), name, email, message (TEXT), locale, category, priority, ipHash (sha256 + APP_SECRET salt, never the raw IP), createdAt, deleteAfter.
- `DemoRun`: id, slug, status (ok|timeout|error|rate_limited), durationMs, locale, requestId, createdAt. No user payloads stored.
- Indexes: `demo_run(slug, created_at)`, `lead(created_at)`.
- Command `app:retention:purge` deletes Leads past `deleteAfter` (default 90 days) and DemoRuns older than 30 days. Register it in a Symfony Scheduler schedule (`#[AsSchedule]`) running daily at 03:00 (consumed by the opt-in `scheduler` worker).
- Migrations only (`doctrine:migrations:migrate`), never `schema:update`.
- `GET /api/stats`: aggregates from DemoRun (runs per demo, p50/p95 latency, success rate, last 7 days), cached in Redis 5 minutes, rendered as a small "live stats" block in the UI.

**Redis (phpredis):**
- `cache.app` pool and a dedicated `cache.rate_limiter` pool use `cache.adapter.redis` (health check, stats, last-executions, limiter state survive deploys and are shared across containers).
- Messenger transport `async` (DSN from `MESSENGER_TRANSPORT_DSN`) with retry strategy (3 tries, exponential) and a `failed` transport (Doctrine). Dispatch asynchronously: Lead persistence, the fallback Telegram/email notification, DemoRun logging. The HTTP request waits only for n8n.
- Config sketch to implement:
```yaml
framework:
  cache:
    default_redis_provider: '%env(REDIS_URL)%'
    pools:
      cache.app: { adapter: cache.adapter.redis }
      cache.rate_limiter: { adapter: cache.adapter.redis, default_lifetime: 86400 }
  rate_limiter:
    demo:
      policy: sliding_window
      limit: 5
      interval: '1 minute'
      cache_pool: cache.rate_limiter
  messenger:
    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        retry_strategy: { max_retries: 3, delay: 1000, multiplier: 3 }
      failed: 'doctrine://default?queue_name=failed'
    failure_transport: failed
```

# 6. LIVE DEMOS (each = Vue component + Symfony endpoint + n8n workflow JSON)
For EVERY demo, generate the importable n8n workflow as `n8n/workflows/<slug>.json` (valid n8n export format: nodes, connections, a Webhook trigger with `responseMode: "responseNode"`, header-auth validation of `X-Webhook-Secret`). Use credential placeholders only, never real keys.

**Demo A: `contact-to-telegram` (lead intake)**
- Input: `{name, email, message, locale}`.
- n8n: Webhook -> Validate/Set -> AI node classifies the lead (`category: sales|support|spam`, `priority: 1-5`, `language`) with structured JSON output -> IF spam: stop -> Telegram node notifies me -> Send Email auto-reply to the visitor IN THEIR LOCALE (en/uk) -> Respond to Webhook `{ok:true, category, priority}`.
- Vue: contact form; after submit shows "what just happened" as a step timeline (received -> classified -> notified -> replied).

**Demo B: `ai-translate-en-uk`**
- Input: `{text (<=1000 chars), direction: "en-uk"|"uk-en", tone: "neutral|formal|casual"}`.
- n8n: Webhook -> LLM node with a strict system prompt -> Respond `{translation, detectedTerms[], tokensUsed}`.

**Demo C: `url-summarizer`**
- Input: `{url}`. n8n: Webhook -> HTTP Request (fetch page) -> HTML Extract -> LLM summary (5 bullets + 1-line TL;DR, in the UI locale) -> Respond.
- SSRF guard in Symfony BEFORE forwarding: allow only http(s), resolve DNS and block private/loopback/link-local ranges, re-check after resolution, cap URL length.

**Demo D: `lead-scoring`**
- Input: free-text company description. Output: `{score:0-100, reasons[], suggestedNextStep}` using the LLM's JSON mode, validated with a JSON Schema node in n8n and re-validated in Symfony.

**Demo E (static showcase): Workflow gallery**
- Render the exported JSON of A-D as interactive graphs (vue-flow) with a node-by-node explanation and a "Download workflow JSON" button. The site is a portfolio and a reusable template library.

For each demo also provide: request/response JSON Schema, an example `curl` against the Symfony endpoint, and loading / error / empty states in the UI.

# 7. SITE STRUCTURE (single page with anchor sections + routes)
1. Hero: name, title, one-line value proposition, CTAs ("Try live demo", "Contact").
2. Services.
3. Live demos (tabs A-D, "n8n live" badge, live stats block).
4. Workflow gallery.
5. Process (audit -> prototype -> production -> support).
6. About + tech stack icons.
7. Contact (Demo A form + direct links).
8. Footer: language switcher, health badge, links.

Design: clean, modern, dark/light theme (prefers-color-scheme + toggle persisted), mobile-first responsive, accessible (WCAG AA, visible focus, `aria-live` for demo results), Lighthouse >= 90 in all categories.

# 8. INTERNATIONALIZATION (EN + UK), strict requirements
- vue-i18n with lazy-loaded locale files; routes prefixed `/en` and `/uk`; `/` redirects by saved choice, then Accept-Language, else `en`.
- The language switcher keeps the current section/route.
- Correct Ukrainian pluralization (3 plural forms) and date/number formatting via Intl.
- No hardcoded UI strings anywhere. Add a Vitest check that `en` and `uk` have identical key sets.
- Symfony side: `translations/messages.{en,uk}.yaml` and `validators.{en,uk}.yaml` for validation errors; Twig email templates in both locales. The API reads the `locale` field / `Accept-Language`.
- SEO: `<html lang>`, `hreflang` alternates + `x-default`, canonical, per-locale title/description/OG tags, JSON-LD `Person` + `Service`, `sitemap.xml` and `robots.txt` generated by Symfony.

# 9. OBSERVABILITY, QUALITY, CI
- Monolog JSON logs with request-id correlation to the n8n call; never log message bodies or emails in plain text (hash or mask).
- PHPUnit: controller tests with `MockHttpClient` covering success, n8n timeout, n8n 500, validation failure, rate limit, SSRF block, bad HMAC. Repository tests run against real MySQL.
- Vitest: i18n key parity, demo component states, language switcher.
- GitHub Actions: composer install, phpstan, phpunit with MySQL 8.4 and Redis 7 service containers, npm ci, lint, typecheck, vitest, vite build, plus a smoke build of the provided images: `docker build -f docker/php/Dockerfile --target prod .` and `--target nginx_prod`.
- README: architecture diagram (mermaid), how to run on the provided Docker setup, how to import the n8n workflows, required n8n credentials, env var table, troubleshooting.

# 10. .env.example (app-side variables, document each one)
`APP_ENV`, `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN`, `MAILER_DSN`, `N8N_BASE_URL=https://your-n8n.example.com`, `N8N_WEBHOOK_SECRET`, `N8N_HMAC_KEY`, `N8N_API_KEY`, `TURNSTILE_SECRET`, `CONTACT_TO_EMAIL`, `TRUSTED_PROXIES` (empty by default).
Never commit real values; `.env.local` is gitignored.

# 11. DELIVERY PHASES (stop and show results after each)
1. Scaffold on the provided Docker setup: Symfony skeleton + Vite/Vue/i18n/Tailwind, Twig shell with `vite_assets()`, hello page in both locales reachable at http://localhost:8080, `vite.config.ts` as specified, `make up` healthy.
2. Backend core: Doctrine entities + first migration, Redis cache/limiter/Messenger, `N8nClient`, `DemoRegistry`, health + stats endpoints, error mapping, CSP subscriber, with tests.
3. n8n workflows A-D as importable JSON + the matching Symfony endpoints and tests.
4. Vue UI: all sections, demo components with states, workflow gallery.
5. SEO, a11y, performance pass; GitHub Actions CI; README.

# 12. HARD RULES
- Output full files with their paths; no "..." elisions in code.
- Do NOT generate or modify Dockerfiles, compose files, nginx or MySQL config (they are provided). If the app needs an infra change, say exactly which one and why, then wait.
- Do not claim workflows already exist on my n8n instance: generate them as JSON for me to import and list exactly which credentials to create (LLM provider, Telegram bot, SMTP).
- Use only documented n8n node types and parameters. If unsure of a node's schema, say so and give the manual UI steps instead of guessing.
- No secrets in the repo. No direct browser-to-n8n calls. No unvalidated user input forwarded to n8n.
- If a requirement is ambiguous, state the assumption in one line and proceed.

# ACCEPTANCE CRITERIA
- `make init && make up` brings the stack up healthy; `/en` and `/uk` render fully translated at http://localhost:8080.
- All 4 demos return real results through your-n8n.example.com once the workflows are imported and activated.
- The health badge reflects the real n8n status; the stats block shows real DemoRun aggregates.
- Rate limits persist across a `php` container restart (Redis-backed).
- Stopping the `messenger` container does not break form submission; queued jobs are processed after it restarts.
- `make fresh` rebuilds the database from migrations without errors.
- Tests, linters, and PHPStan pass in CI; both prod image targets build.

## ======== PROMPT END ========
