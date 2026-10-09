<script setup>
defineOptions({ inheritAttrs: false });
defineProps({
    iconOnly: { type: Boolean, default: false },
    customUrl: { type: String, default: '' },
    alt: { type: String, default: 'Zavelyx' },
});
</script>

<template>
    <span v-bind="$attrs" class="inline-flex items-center flex-shrink-0">
        <img v-if="customUrl && !iconOnly" :src="customUrl" :alt="alt" class="block h-full w-auto max-w-full object-contain" />
        <img v-else-if="iconOnly" src="/branding/zavelyx-symbol.png?v=20261009" :alt="alt" class="block h-full w-full object-contain" />
        <template v-else>
            <img src="/branding/zavelyx-logo-light.png?v=20261009" :alt="alt" class="block h-full w-auto max-w-full object-contain dark:hidden" />
            <img src="/branding/zavelyx-logo-dark.png?v=20261009" :alt="alt" class="hidden h-full w-auto max-w-full object-contain dark:block" />
        </template>
    </span>
</template>
