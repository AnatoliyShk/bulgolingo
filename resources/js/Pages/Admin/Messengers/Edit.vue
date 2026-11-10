<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import MessengerForm from '@/Components/Forms/MessengerForm.vue';

defineProps({
    messenger: Object,
    users: Array,
    messengerNames: { type: Array, required: true },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Messengers', href: route('admin.messengers.index') },
                { label: messenger.messenger_label },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Messenger Details</h3>
                    <MessengerForm :messenger="messenger" :users="users" :messenger-names="messengerNames" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
