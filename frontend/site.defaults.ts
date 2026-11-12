/**
 * Site identity — configured via env vars (SITE_*, CONTACT_*), injected by
 * Vite (envPrefix) at build time and by Symfony at runtime.
 *
 * Anonymization: NO personal defaults live here. A deployer sets their own
 * values in `.env` (see `.env.example`). When a var is unset the site shows
 * a neutral placeholder (empty) rather than a hardcoded owner identity.
 */
export const SITE = {
  name: import.meta.env.SITE_NAME ?? '',
  nameUk: import.meta.env.SITE_NAME_UK ?? '',
  email: import.meta.env.CONTACT_EMAIL ?? '',
  domain: import.meta.env.SITE_DOMAIN ?? '',
  company: import.meta.env.COMPANY_NAME ?? '',
  telegramUser: import.meta.env.TELEGRAM_USER ?? '',
  telegramUrl: import.meta.env.TELEGRAM_URL ?? '',
  linkedinSlug: import.meta.env.LINKEDIN_SLUG ?? '',
  linkedinUrl: import.meta.env.LINKEDIN_URL ?? '',
  githubUser: import.meta.env.GITHUB_USER ?? '',
  githubUrl: import.meta.env.GITHUB_URL ?? '',
} as const;

export type SiteIdentity = typeof SITE;
