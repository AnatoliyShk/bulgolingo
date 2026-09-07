<script setup>
import '@/assets/scss/components/admin/pagination.scss';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
    only: {
        type: Array,
        default: () => [],
    },
    preserveState: {
        type: Boolean,
        default: false,
    },
});

const hasPages = computed(() => (props.paginator.last_page ?? 1) > 1);

const pages = computed(() => (props.paginator.links ?? []).map((link, index) => ({
    ...link,
    key: index,
    text: link.label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next')
        .replace('&hellip;', '…'),
})));
</script>

<template>
    <nav v-if="paginator.total > 0" class="admin-pagination" aria-label="Pagination">
        <p class="admin-pagination__summary" data-testid="pagination-summary">
            Showing {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
        </p>

        <ul v-if="hasPages" class="admin-pagination__pages">
            <li v-for="link in pages" :key="link.key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :only="only"
                    :preserve-state="preserveState"
                    preserve-scroll
                    class="admin-pagination__link"
                    :class="{ 'admin-pagination__link--current': link.active }"
                    :aria-current="link.active ? 'page' : undefined"
                >{{ link.text }}</Link>
                <span
                    v-else
                    class="admin-pagination__link admin-pagination__link--disabled"
                >{{ link.text }}</span>
            </li>
        </ul>
    </nav>
</template>
