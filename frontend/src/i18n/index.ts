import { createI18n, type I18n } from 'vue-i18n';
import { detectLocale, setI18nSingleton, type Locale } from '../stores/locale';

async function loadLocale(lang: Locale) {
  // The locale module is produced by our i18nPrecompilePlugin (vite.i18n.ts),
  // which pre-compiles every message string into a message FUNCTION (so the
  // runtime-only vue-i18n bundle can interpolate without `new Function()`).
  //
  // The module exposes BOTH named exports (one per top-level key) AND a
  // `default` export (the whole tree). We prefer `default` when present because
  // Rollup sometimes dedupes/collapses named exports into the default in the
  // minified bundle — relying on named exports alone made messages empty and
  // every t() call return the raw key path (e.g. "demos.heading").
  const mod = (await import(`../locales/${lang}.json`)) as {
    default?: Record<string, unknown>;
    [key: string]: unknown;
  };

  if (mod.default && typeof mod.default === 'object') {
    return mod.default as Record<string, unknown>;
  }

  // Fallback: collect named exports (Vite's JSON-import shape), skipping default.
  const messages: Record<string, unknown> = {};
  for (const [key, value] of Object.entries(mod)) {
    if (key === 'default' || value === undefined) continue;
    messages[key] = value;
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
