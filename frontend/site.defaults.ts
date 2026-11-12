/**
 * Anonymization: personal identity is configured via env vars (SITE_*),
 * injected by Vite (envPrefix) at build time and by Symfony at runtime.
 * These are the fallback defaults so the project works out-of-the-box.
 * A deployer overrides them by filling .env (see .env.example).
 */
export const SITE = {
  name: import.meta.env.SITE_NAME ?? 'Yaroslav Dupliak',
  nameUk: import.meta.env.SITE_NAME_UK ?? 'Ярослав Дупляк',
  email: import.meta.env.CONTACT_EMAIL ?? 'info@novotek.com.ua',
  domain: import.meta.env.SITE_DOMAIN ?? 'novotek.com.ua',
  company: import.meta.env.COMPANY_NAME ?? 'Novotek',
  telegramUser: import.meta.env.TELEGRAM_USER ?? 'dyar74',
  telegramUrl: import.meta.env.TELEGRAM_URL ?? 'https://t.me/dyar74',
  linkedinSlug: import.meta.env.LINKEDIN_SLUG ?? 'duplyak',
  linkedinUrl: import.meta.env.LINKEDIN_URL ?? 'https://www.linkedin.com/in/duplyak/',
  githubUser: import.meta.env.GITHUB_USER ?? 'dyar74',
  githubUrl: import.meta.env.GITHUB_URL ?? 'https://github.com/dyar74/',
} as const;

export type SiteIdentity = typeof SITE;
