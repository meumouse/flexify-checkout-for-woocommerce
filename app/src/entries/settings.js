/**
 * Settings app entry point.
 *
 * @since 6.0.0
 */
import '../styles/main.css';
import SettingsPage from '../pages/settings/SettingsPage.vue';
import { mountPage } from '../utils/bootstrap';

mountPage('flexify-checkout-settings-app', SettingsPage);
