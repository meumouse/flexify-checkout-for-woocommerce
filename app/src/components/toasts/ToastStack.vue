<script setup>
/**
 * Toast host for the admin app.
 *
 * Renders the vue-sonner <Toaster>; toasts are pushed imperatively from the
 * settings store via `toast` (see useSettingsStore.pushToast). Kept as a small
 * named component so the settings/apps pages can mount it where the stack used
 * to live.
 *
 * The card stays neutral (shadcn Sonner look); each type is distinguished by a
 * single coloured lucide-style icon supplied through the per-type icon slots.
 */
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
</script>

<template>
  <Toaster position="top-right" close-button :duration="4000">
    <template #success-icon>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #16a34a" aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="m9 12 2 2 4-4" />
      </svg>
    </template>
    <template #error-icon>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #dc2626" aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="m15 9-6 6" />
        <path d="m9 9 6 6" />
      </svg>
    </template>
    <template #info-icon>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #2563eb" aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 16v-4" />
        <path d="M12 8h.01" />
      </svg>
    </template>
    <template #warning-icon>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #d97706" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
        <path d="M12 9v4" />
        <path d="M12 17h.01" />
      </svg>
    </template>
  </Toaster>
</template>

<!--
  Unscoped on purpose: vue-sonner renders the <Toaster> into a portal at the
  document root, so scoped styles would never reach it. Places the close button
  at the trailing end of the toast, vertically centered, instead of floating in
  the top corner. The leading [data-sonner-toaster] out-specifies vue-sonner's
  own rule without !important; the right padding keeps the message clear of the
  button.
-->
<style>
[data-sonner-toaster] [data-sonner-toast][data-styled='true'] {
  padding-right: 40px;
}

[data-sonner-toaster] [data-sonner-toast][data-styled='true'] [data-close-button] {
  left: auto;
  right: 12px;
  top: 50%;
  bottom: auto;
  transform: translateY(-50%);
}
</style>
