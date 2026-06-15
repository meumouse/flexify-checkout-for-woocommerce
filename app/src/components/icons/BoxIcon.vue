<script setup>
/**
 * Boxicons wrapper.
 *
 * Renders an official Boxicons SVG inline so the whole admin shares a single
 * icon set. Pass the icon name without the `bx-`/`bxs-`/`bxl-` prefix and pick
 * a variant via `type` ('regular' is the outline set used for navigation, tabs
 * and most UI; 'solid' for badges/filled states; 'logos' for brand marks).
 *
 * Color follows the surrounding text (`currentColor`) and size follows the
 * element box, so style it with utility classes, e.g.:
 *   <BoxIcon name="save" class="h-5 w-5 text-primary" />
 *
 * @since 6.0.0
 */
import { computed } from 'vue';
import icons from './icons.js';

const props = defineProps({
  /** Icon name without prefix, e.g. 'save', 'cart', 'chevron-up'. */
  name: { type: String, required: true },
  /** Boxicons variant: 'regular' | 'solid' | 'logos'. */
  type: {
    type: String,
    default: 'regular',
    validator: (value) => ['regular', 'solid', 'logos'].includes(value),
  },
});

// Only the icons the admin actually uses are bundled (see ./icons.js). Look the
// requested icon up by "type/name"; unknown icons render nothing (and warn in
// dev) so a typo is obvious without breaking the build.
const markup = computed(() => {
  const key = `${props.type}/${props.name}`;
  const svg = icons[key];

  if (!svg) {
    if (import.meta.env.DEV) {
      // eslint-disable-next-line no-console
      console.warn(`[BoxIcon] Unknown icon "${key}". Add it to src/components/icons/icons.js.`);
    }

    return '';
  }

  return svg;
});
</script>

<template>
  <span class="flexify-bx-icon" aria-hidden="true" v-html="markup" />
</template>

<style scoped>
.flexify-bx-icon {
  display: inline-flex;
  line-height: 0;
}

.flexify-bx-icon :deep(svg) {
  width: 100%;
  height: 100%;
  display: block;
  fill: currentColor;
}
</style>
