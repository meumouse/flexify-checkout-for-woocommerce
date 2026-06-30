<script setup>
/**
 * Toast host for the admin app.
 *
 * Renders the vue-sonner <Toaster>; toasts are pushed imperatively from the
 * settings store via `toast` (see useSettingsStore.pushToast). Kept as a small
 * named component so the settings/apps pages can mount it where the stack used
 * to live.
 */
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
</script>

<template>
  <Toaster position="top-right" rich-colors close-button :duration="4000" />
</template>
