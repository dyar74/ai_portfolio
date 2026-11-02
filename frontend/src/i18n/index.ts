import { createI18n, type I18n } from 'vue-i18n';
import { detectLocale, setI18nSingleton, type Locale } from '../stores/locale';

async function loadLocale(lang: Locale) {
  // Vite serves .json as a module of NAMED exports (one per top-level key),
  // NOT a `default` export. So we collect all named exports into a plain object
  // (skipping `default`/symbol keys) and hand that to vue-i18n.
  const mod = (await import(`../locales/${lang}.json`)) as Record<string, unknown>;
  const messages: Record<string, unknown> = {};
  for (const [key, value] of Object.entries(mod)) {
    if (key === 'default') continue; // defensive: ignore if a default ever appears
    if (value !== undefined) messages[key] = value;
  }
  return messages;
}

/**
 * Build the i18n instance with BOTH locales preloaded (en + uk) so runtime
 * switching is instant. Active locale comes from localStorage -> 'en'.
 * uk uses its own pluralization rules.
 */
export async function createI18nInstance(): Promise<I18n> {
  const initial = detectLocale();

  const [en, uk] = await Promise.all([loadLocale('en'), loadLocale('uk')]);

  const instance = createI18n({
    legacy: false,
    locale: initial,
    fallbackLocale: 'en',
    messages: { en, uk },
    pluralRules: {
      uk: (choice: number, _choicesLength: number) => {
        const n = Math.abs(choice);
        const mod10 = n % 10;
        const mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return 0;
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 1;
        return 2;
      },
    },
  } as Parameters<typeof createI18n>[0]);

  // Register the instance so `useLocale()` can drive `global.locale` from
  // anywhere (including router guards) without calling `useI18n()`.
  setI18nSingleton(instance);

  return instance;
}
