<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import BotForm from '@/Components/Forms/BotForm.vue';

defineProps({
    bot: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Bots', href: route('admin.bots.index') },
                { label: bot.name },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Bot Details</h3>
                    <BotForm :bot="bot" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
