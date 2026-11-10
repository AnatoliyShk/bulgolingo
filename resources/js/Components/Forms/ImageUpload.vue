<script setup>
import '@/assets/scss/components/admin/_admin-image-upload.scss';
import { ref } from 'vue';

const props = defineProps({
    existingUrl: { type: String, default: null },
    error:       { type: String, default: null },
});

const emit = defineEmits(['change']);

const preview = ref(null);
const dragging = ref(false);

function onFileChange(event) {
    const file = event.target.files[0] ?? null;
    setFile(file);
}

function onDrop(event) {
    dragging.value = false;
    const file = event.dataTransfer.files[0] ?? null;
    if (file && file.type.startsWith('image/')) setFile(file);
}

function setFile(file) {
    preview.value = file ? URL.createObjectURL(file) : null;
    emit('change', file);
}

function clearImage() {
    preview.value = null;
    emit('change', null);
}
</script>

<template>
    <div class="image-upload">
        <label class="image-upload__label">Image</label>

        <div
            class="image-upload__dropzone"
            :class="{ 'image-upload__dropzone--active': dragging, 'image-upload__dropzone--error': error }"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <template v-if="preview || existingUrl">
                <div class="image-upload__preview-wrap">
                    <img
                        :src="preview || existingUrl"
                        alt="Preview"
                        class="image-upload__preview-img"
                    />
                    <div class="image-upload__overlay">
                        <label class="image-upload__change-btn">
                            Change
                            <input type="file" accept="image/*" class="image-upload__file-input" @change="onFileChange" />
                        </label>
                        <button type="button" class="image-upload__clear-btn" @click="clearImage">Remove</button>
                    </div>
                </div>
                <p v-if="existingUrl && !preview" class="image-upload__hint">Upload a new image to replace the current one.</p>
            </template>

            <template v-else>
                <div class="image-upload__placeholder">
                    <svg class="image-upload__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4-4a3 3 0 014 0l4 4m0 0l1-1a3 3 0 014 0l3 3M4 20h16M12 4v8m0 0l-3-3m3 3l3-3" />
                    </svg>
                    <p class="image-upload__cta">Drop an image or <label class="image-upload__browse">browse<input type="file" accept="image/*" class="image-upload__file-input" @change="onFileChange" /></label></p>
                    <p class="image-upload__formats">PNG, JPG, GIF, WEBP</p>
                </div>
            </template>
        </div>

        <p v-if="error" class="image-upload__error">{{ error }}</p>
    </div>
</template>
