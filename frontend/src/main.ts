import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { createI18nInstance } from './i18n';

import './assets/main.css';

async function bootstrap() {
  const i18n = await createI18nInstance();
  const app = createApp(App);
  app.use(createPinia());
  app.use(i18n);
  app.use(router);
  app.mount('#app');
}

void bootstrap();
