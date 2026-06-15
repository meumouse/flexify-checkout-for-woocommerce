/**
 * Admin SPA entry point.
 *
 * Mounts the routed admin application. The initial route is taken from the
 * `view` exposed by WordPress in flexifyCheckoutBootstrapConfig, so each WP
 * submenu page (Configurações, Licença) opens the app on the right route.
 *
 * @since 6.0.0
 */
import '../styles/main.css';
import App from '../App.vue';
import { createAppRouter } from '../router';
import { mountRoutedPage, readBootstrapConfig } from '../utils/bootstrap';

const allowedViews = ['settings', 'apps', 'license', 'analytics', 'carts', 'queue'];
const view = readBootstrapConfig()?.view;
const initialPath = `/${allowedViews.includes(view) ? view : 'settings'}`;

mountRoutedPage('flexify-checkout-settings-app', App, createAppRouter(initialPath));
