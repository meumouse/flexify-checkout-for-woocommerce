/**
 * Admin SPA router.
 *
 * Uses memory history on purpose: the Flexify Checkout admin lives inside
 * wp-admin where the URL query string (?page=) drives navigation between WP
 * submenu pages, and SettingsPage already owns the location hash for its tab
 * deep-linking. Memory history gives us real route components / <router-view>
 * without hijacking either. The initial route is provided by the page that
 * mounts the app (see entries/settings.js) from the localized `view`.
 *
 * @since 6.0.0
 */
import { createRouter, createMemoryHistory } from 'vue-router';
import SettingsPage from '../pages/settings/SettingsPage.vue';
import LicensePage from '../pages/license/LicensePage.vue';

const routes = [
  { path: '/settings', name: 'settings', component: SettingsPage },
  { path: '/license', name: 'license', component: LicensePage },
  { path: '/:pathMatch(.*)*', redirect: '/settings' },
];

/**
 * Build a router instance positioned at the requested initial path.
 *
 * @since 6.0.0
 * @param {string} initialPath - Route to open on mount (defaults to /settings).
 * @return {import('vue-router').Router}
 */
export function createAppRouter(initialPath = '/settings') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes,
  });

  router.replace(initialPath);

  return router;
}
