import { ref } from 'vue'

const theme = ref(
    typeof localStorage !== 'undefined'
        ? (localStorage.getItem('theme') || 'light')
        : 'light'
)

// cc--darkmode is how vanilla-cookieconsent picks its palette, so the consent
// modals follow the toggle instead of sitting light against a dark page.
function applyTheme(value) {
    const html = document.documentElement
    if (value === 'dark') {
        html.classList.add('dark')
        html.classList.remove('light')
    } else {
        html.classList.remove('dark')
        html.classList.add('light')
    }
    html.classList.toggle('cc--darkmode', value === 'dark')
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
