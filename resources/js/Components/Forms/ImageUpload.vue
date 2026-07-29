<script setup>
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

<style scoped>
.image-upload__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}

.image-upload__dropzone {
    border: 2px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 1.25rem;
    transition: border-color 0.2s, background-color 0.2s;
    background-color: transparent;
}

.image-upload__dropzone--active {
    border-color: #6366f1;
    background-color: rgba(99, 102, 241, 0.05);
}

.image-upload__dropzone--error {
    border-color: #ef4444;
}

.image-upload__preview-wrap {
    position: relative;
    display: inline-block;
}

.image-upload__preview-img {
    width: 8rem;
    height: 8rem;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    display: block;
}

.image-upload__overlay {
    position: absolute;
    inset: 0;
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.image-upload__preview-wrap:hover .image-upload__overlay {
    opacity: 1;
}

.image-upload__change-btn,
.image-upload__clear-btn {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    border: none;
}

.image-upload__change-btn {
    background: #6366f1;
    color: #fff;
}

.image-upload__clear-btn {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.image-upload__clear-btn:hover {
    background: rgba(239, 68, 68, 0.7);
}

.image-upload__placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0;
}

.image-upload__icon {
    width: 2.5rem;
    height: 2.5rem;
    color: #9ca3af;
}

.image-upload__cta {
    font-size: 0.875rem;
    color: #6b7280;
}

.image-upload__browse {
    color: #6366f1;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.image-upload__formats {
    font-size: 0.72rem;
    color: #9ca3af;
    letter-spacing: 0.04em;
}

.image-upload__file-input {
    display: none;
}

.image-upload__hint {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.image-upload__error {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #ef4444;
}

@media (prefers-color-scheme: dark) {
    .image-upload__label  { color: #d1d5db; }
    .image-upload__dropzone { border-color: #4b5563; }
    .image-upload__dropzone--active { border-color: #818cf8; background-color: rgba(99,102,241,0.08); }
    .image-upload__preview-img { border-color: #374151; }
    .image-upload__cta  { color: #9ca3af; }
    .image-upload__browse { color: #818cf8; }
    .image-upload__hint { color: #9ca3af; }
    .image-upload__icon { color: #6b7280; }
}
</style>
