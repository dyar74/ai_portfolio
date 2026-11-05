import { createRouter, createWebHistory } from 'vue-router';
import { useLocale, type Locale } from '../stores/locale';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Main locale routes (catch-all)
    { path: '/en/:pathMatch(.*)*', name: 'en', component: () => import('../views/LocalePage.vue'), props: { locale: 'en' } },
    { path: '/uk/:pathMatch(.*)*', name: 'uk', component: () => import('../views/LocalePage.vue'), props: { locale: 'uk' } },
    { path: '/:pathMatch(.*)*', redirect: '/en' },
  ],
});

router.beforeEach((to) => {
  const locale = useLocale();
  const target: Locale | undefined = to.name === 'uk' ? 'uk' : to.name === 'en' ? 'en' : undefined;
  if (target && locale.current() !== target) {
    locale.set(target);
  }
  return true;
});

export default router;
