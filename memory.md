# Project Memory — my_business_card

## Environment & Deployment

- **[2026-11-05]** `docker compose --env-file portfolio-docker/.env.docker` fails: (1) user cd'd OUT of repo, breaking relative paths; (2) `.env.docker` had empty required vars. Fix: run from repo root, use `make prod-build`.
- **[2026-11-05]** Two `.env` files caused confusion. Consolidated to **single repo-root `.env`** (git-ignored, auto-loaded by Compose). `portfolio-docker/.env.docker` deleted. `docker-compose.prod.yml` uses `:-` (optional) for N8N secrets. `envgen.py` derives DSNs from credential parts.
- **[2026-11-09]** **Auto-deploy** via GitHub Actions → SSH → `bash portfolio-docker/deploy.sh`. Push to `main` triggers CI + deploy. `deploy.sh` does: git pull → `make prod-build` → `make prod-migrate` → `make prod-up` → health check.
- **[2026-11-12]** **Orphan container warning** fixed: all `up`/`down` Make targets now include `--remove-orphans` (`up`, `down`, `prod-up`, `prod-down`). `deploy.sh` calls `make prod-up` so it inherits the fix.

## Frontend / i18n

- **[2026-11-09]** **CSP-safe i18n build:** Replaced runtime `new Function()` (banned by CSP) with:
  - `vite.i18n.ts` — Vite plugin that pre-compiles `en.json`/`uk.json` into `defineMessage()` chunks at build time. Named interpolation (`{variable}`) works in runtime-only Vue + vue-i18n.
  - `i18n/index.ts` — uses `mod.default` to handle Rollup's named→default export collapsing.
  - Build output: `_i18n-precompiled_en-*.js` + `_i18n-precompiled_uk-*.js` chunks.
- **[2026-11-11]** **Mobile nav:** Replaced horizontal scroll bar (`overflow-x-auto`) with hamburger menu. 6 nav buttons (~476px) didn't fit in 375px viewport. Now: ☰ toggle → vertical button list (`v-show`), auto-closes on tap.
- **[2026-11-12]** **Centering fixes:**
  - `SectionHeading.vue`: all 6 section h2s + subheadings centered (`text-center` + `mx-auto`).
  - `DemosSection.vue`: HealthBadge moved below heading (was right-aligned).
  - Demo cards (4): h3 titles + descriptions centered.
  - `Services`, `Process`, `About`: sub-headers centered.
  - `WorkflowGallery.vue`: card header `flex-col items-center` (button `w-full` on mobile, `w-auto` on desktop).
  - `WorkflowGraph.vue`: nodes at `x=0` (was `x=60`) so `fitViewOnInit` centers the 200px column in any viewport.

## Backend

- **[2026-11-05]** **Single env file:** repo-root `.env` is the single source of truth. `envgen.py` derives `DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN` from credential parts (`MYSQL_USER`, `MYSQL_PASSWORD`, `REDIS_PASSWORD`). `docker-compose.prod.yml` re-derives the same DSNs.
- **[2026-11-09]** **CSP subscriber:** Symfony sets `Content-Security-Policy` headers. Nginx must NOT override. Inline JSON-LD is allowed via `script-src 'unsafe-inline'` for that specific script tag.

## Key Decisions

| Decision | Rationale |
|---|---|
| Browser never calls n8n directly | BFF handles rate limiting, validation, HMAC, error mapping |
| Pre-compiled i18n (Vite plugin) | CSP bans `new Function()`; runtime-only Vue needs pre-compiled messages |
| `mod.default` in i18n/index.ts | Rollup collapses named exports to default in ESM bundles |
| Hamburger menu (not scroll bar) | 6 buttons don't fit in 375px; standard mobile pattern |
| Centered headers | Consistent, clean look across all sections |
| `--remove-orphans` on all up/down | Eliminates orphan container warnings |
| Single `.env` (repo root) | One source of truth; `envgen.py` derives DSNs |
| `deploy.sh` calls `make` targets | Single place to manage build/migrate/up/health sequence |
