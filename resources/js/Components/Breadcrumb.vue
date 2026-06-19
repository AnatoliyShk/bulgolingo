<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ items: Array });
const parentHref = computed(() => {
    const parent = props.items?.at(-2);
    return parent?.href ?? null;
});
</script>

<template>
    <nav class="flex items-center gap-1.5 text-sm">
        <Link
            v-if="parentHref"
            :href="parentHref"
            class="mr-1 flex items-center justify-center rounded p-1 text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-200 transition-colors"
            aria-label="Go back"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </Link>
        <template v-for="(item, i) in items" :key="i">
            <span v-if="i > 0" class="text-gray-400 dark:text-gray-500">/</span>
            <Link
                v-if="item.href"
                :href="item.href"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >{{ item.label }}</Link>
            <span v-else class="font-semibold text-gray-800 dark:text-gray-200">{{ item.label }}</span>
        </template>
    </nav>
</template>
