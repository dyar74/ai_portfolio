/// <reference types="vite/client" />

/**
 * Site identity env vars (exposed via Vite envPrefix in vite.config.ts).
 * Configured in .env for anonymization/re-branding.
 */
interface ImportMetaEnv {
  readonly SITE_NAME?: string;
  readonly SITE_NAME_UK?: string;
  readonly CONTACT_EMAIL?: string;
  readonly SITE_DOMAIN?: string;
  readonly COMPANY_NAME?: string;
  readonly TELEGRAM_USER?: string;
  readonly TELEGRAM_URL?: string;
  readonly LINKEDIN_SLUG?: string;
  readonly LINKEDIN_URL?: string;
  readonly GITHUB_USER?: string;
  readonly GITHUB_URL?: string;
  readonly VITE_DEV_HOST?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
