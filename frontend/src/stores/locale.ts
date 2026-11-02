import type { I18n, Composer } from 'vue-i18n';

export type Locale = 'en' | 'uk';

const STORAGE_KEY = 'portfolio.locale';

/**
 * App-level i18n instance, set once in `i18n/index.ts` when the instance is
 * created (before the app boots). Reading it here avoids calling `useI18n()`
 * (which must run at the top of a component `setup()` and therefore cannot be
 * used inside a router guard).
 */
let i18nSingleton: I18n | null = null;

export function setI18nSingleton(instance: I18n): void {
  i18nSingleton = instance;
}

export function getI18nSingleton(): I18n {
  if (!i18nSingleton) {
    throw new Error('i18n instance not initialised — call setI18nSingleton() first.');
  }
  return i18nSingleton;
}

export function detectLocale(): Locale {
  if (typeof localStorage === 'undefined') return 'en';
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'en' || stored === 'uk') return stored;
  return 'en';
}

export function persistLocale(l: Locale): void {
  if (typeof localStorage === 'undefined') return;
  localStorage.setItem(STORAGE_KEY, l);
}

/**
 * Locale composable.
 *
 * Implemented WITHOUT `useI18n()` so it is safe to call from a router
 * `beforeEach` guard (where `useI18n()` would throw "Must be called at the top
 * of a `setup` function"). It drives the app-level i18n instance's reactive
 * `global.locale` directly, so switching here re-renders every component.
 *
 * `current()` / `set()` keep the same shape as before, so existing call sites
 * (Header, ContactDemo, TranslateDemo) work unchanged.
 */
export function useLocale() {
  const global: Composer = getI18nSingleton().global as Composer;

  const current = (): Locale => (global.locale.value as unknown as string) as Locale;

  function set(l: Locale): void {
    // `I18n` (composition mode) has no top-level `locale` — the reactive
    // WritableComputedRef lives on `i18n.global.locale`. Writing to it
    // reliably triggers a re-render of every component using `t()` /
    // `useI18n()`.
    global.locale.value = l;
    persistLocale(l);
  }

  return { current, set };
}
