import { ref } from 'vue'

const theme = ref(
    typeof localStorage !== 'undefined'
        ? (localStorage.getItem('theme') || 'dark')
        : 'dark'
)

function applyTheme(value) {
    const html = document.documentElement
    if (value === 'dark') {
        html.classList.add('dark')
        html.classList.remove('light')
    } else {
        html.classList.remove('dark')
        html.classList.add('light')
    }
}

export function useTheme() {
    function toggleTheme() {
        theme.value = theme.value === 'dark' ? 'light' : 'dark'
        localStorage.setItem('theme', theme.value)
        applyTheme(theme.value)
    }

    // Ensure DOM matches state on first use
    applyTheme(theme.value)

    return { theme, toggleTheme }
}
