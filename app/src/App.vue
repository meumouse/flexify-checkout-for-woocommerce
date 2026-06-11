<script setup>
/**
 * Admin SPA root shell.
 *
 * Hydrates the settings store once from the bootstrap payload so every route
 * (Settings, License) shares the same loaded state, then renders the active
 * route. The bootstrap prop is forwarded to route components so the existing
 * SettingsPage continues to receive it unchanged.
 *
 * @since 6.0.0
 */
import { useSettingsStore } from './stores/useSettingsStore';

const props = defineProps({
  bootstrap: { type: Object, default: () => ({}) },
});

const store = useSettingsStore();

store.hydrate(props.bootstrap);
</script>

<template>
  <router-view :bootstrap="bootstrap" />
</template>
