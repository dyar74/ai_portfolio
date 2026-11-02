<template>
  <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
      <button
        type="button"
        :aria-label="t('nav.to', { section: t('hero.title') })"
        class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-gray-100"
        @click="goToSection('hero')"
      >
        <ProfilePhoto :size="36" />
        <span class="hidden sm:inline">{{ t('meta.siteName') }}</span>
      </button>

      <nav aria-label="primary" class="hidden items-center gap-1 md:flex">
        <button
          v-for="item in navItems"
          :key="item.id"
          type="button"
          :aria-label="t('nav.to', { section: item.label })"
          class="rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
          @click="goToSection(item.id)"
        >
          {{ item.label }}
        </button>
      </nav>

      <div class="flex items-center gap-2">
        <!-- Theme toggle -->
        <button
          type="button"
          :aria-label="isDark ? t('theme.toLight') : t('theme.toDark')"
          :title="isDark ? t('theme.toLight') : t('theme.toDark')"
          class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
          @click="theme.toggle()"
        >
          {{ isDark ? '☀️' : '🌙' }}
        </button>

        <!-- Language switcher -->
        <div role="group" :aria-label="t('lang.label')" class="flex overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
          <button
            type="button"
            :class="langBtn('en')"
            :aria-pressed="locale === 'en'"
            @click="switchLocale('en')"
          >
            EN
          </button>
          <button
            type="button"
            :class="langBtn('uk')"
            :aria-pressed="locale === 'uk'"
            @click="switchLocale('uk')"
          >
            UK
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile nav -->
    <nav aria-label="primary-mobile" class="flex gap-1 overflow-x-auto border-t border-gray-200 px-2 py-2 dark:border-gray-800 md:hidden">
      <button
        v-for="item in navItems"
        :key="item.id"
        type="button"
        :aria-label="t('nav.to', { section: item.label })"
        class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
        @click="goToSection(item.id)"
      >
        {{ item.label }}
      </button>
    </nav>
  </header>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useTheme } from '../../stores/theme';
import { useLocale, type Locale } from '../../stores/locale';
import ProfilePhoto from '../ProfilePhoto.vue';

const { t, locale } = useI18n();
const theme = useTheme();
const localeStore = useLocale();
const router = useRouter();

const isDark = computed(() => theme.isDark);

const navItems = computed(() => [
  { id: 'services', label: t('nav.services') },
  { id: 'demos', label: t('nav.demos') },
  { id: 'gallery', label: t('nav.gallery') },
  { id: 'process', label: t('nav.process') },
  { id: 'about', label: t('nav.about') },
  { id: 'contact', label: t('nav.contact') },
]);

/**
 * In-page anchor scroll. Uses buttons (not <a href="#id">) so clicking a
 * section never changes the URL path — which prevents a full page reload to `/`
 * (served in EN) when the user is on the root path. The locale is preserved.
 */
function goToSection(id: string) {
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  // Keep the hash in sync for deep-linking without triggering a reload.
  history.replaceState(null, '', `#${id}`);
}

function langBtn(l: Locale): string {
  const base = 'px-3 py-1.5 text-xs font-semibold transition-colors';
  return locale.value === l
    ? `${base} bg-blue-600 text-white`
    : `${base} bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700`;
}

function switchLocale(l: Locale) {
  // Update the active i18n locale and persist the choice…
  localeStore.set(l);
  // …and navigate to the matching route (/en or /uk) so the choice survives a
  // hard reload. Preserve the current section hash for deep-linking.
  const hash = window.location.hash || '';
  void router.push({ name: l, hash });
}

// If the user hard-reloads while on `/` (EN), warn them so the locale choice
// isn't silently lost. On /en or /uk a reload is safe and is allowed through.
function onBeforeUnload(e: BeforeUnloadEvent) {
  if (locale.value === 'uk' && window.location.pathname !== '/uk') {
    e.preventDefault();
    e.returnValue = '';
    return '';
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', onBeforeUnload);
});
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', onBeforeUnload);
});
</script>
