import { router } from '@inertiajs/vue3'

// Asks before a destructive request and sends it only once the user agrees.
export function confirmVisit(message, url, method = 'delete') {
    if (confirm(message)) {
        router.visit(url, { method })
    }
}
