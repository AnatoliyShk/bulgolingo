<script setup>
import '@/assets/scss/components/theme-toggle.scss'
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()
</script>

<template>
    <button
        type="button"
        class="nb-toggle"
        :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
        @click="toggleTheme"
    >{{ theme === 'dark' ? '☀' : '☾' }}</button>
</template>
