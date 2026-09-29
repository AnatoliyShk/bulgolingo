<script setup>
import '@/assets/scss/components/admin/tutor-bot-switch.scss'
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'

useTheme()

const page = usePage()
const enabled = computed(() => page.props.tutorEnabled ?? false)
const saving = ref(false)

// Saves the opposite of the current state and lets the welcome page reload
// its tutorEnabled prop, so the widget mounts or unmounts from the server's
// answer rather than an optimistic guess.
function toggle() {
    if (saving.value) return

    router.put(
        route('admin.settings.tutor-bot'),
        { enabled: !enabled.value },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        }
    )
}
</script>

<template>
    <div class="admin-tutor-switch">
        <span id="admin-tutor-switch-label" class="admin-tutor-switch__label">Tutor bot</span>
        <button
            type="button"
            role="switch"
            class="admin-tutor-switch__track"
            :class="{ 'admin-tutor-switch__track--on': enabled }"
            :aria-checked="enabled"
            :disabled="saving"
            aria-labelledby="admin-tutor-switch-label"
            @click="toggle"
        >
            <span class="admin-tutor-switch__knob" />
        </button>
        <span class="admin-tutor-switch__state">{{ enabled ? 'On' : 'Off' }}</span>
    </div>
</template>
