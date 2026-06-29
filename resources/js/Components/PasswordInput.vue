<script setup>
import '@/assets/scss/components/password-input.scss';
import { onMounted, ref } from 'vue';
import { useTheme } from '@/composables/useTheme';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, required: true });

defineProps({
    // Visual classes for the underlying input (e.g. `nb-auth__input` or Tailwind utilities).
    inputClass: { type: String, default: '' },
});

const { theme } = useTheme();

const input = ref(null);
const visible = ref(false);
const toggle = () => { visible.value = !visible.value; };

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="pw-field" :class="theme">
        <input
            ref="input"
            v-model="model"
            v-bind="$attrs"
            :type="visible ? 'text' : 'password'"
            class="pw-field__input"
            :class="inputClass"
        />
        <button
            type="button"
            class="pw-field__toggle"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            :aria-pressed="visible"
            @click="toggle"
        >
            <font-awesome-icon :icon="visible ? 'eye' : 'eye-slash'" />
        </button>
    </div>
</template>
