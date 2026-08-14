<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-slate-200 shadow-sm focus:border-sky-400 focus:ring-sky-400/30 dark:border-white/12 dark:bg-[var(--surface-elevated)] dark:text-slate-200 dark:focus:border-sky-500 dark:focus:ring-sky-500/30"
        v-model="model"
        ref="input"
    />
</template>
