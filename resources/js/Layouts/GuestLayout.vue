<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900"
    >
        <div class="flex items-center justify-between w-full max-w-md px-4 pt-2 sm:pt-0">
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
            <button
                @click="toggleTheme"
                class="rounded-md border border-gray-200 px-2 py-1 text-sm text-gray-500 transition hover:border-gray-400 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-500"
                :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
            >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800"
        >
            <slot />
        </div>
    </div>
</template>
