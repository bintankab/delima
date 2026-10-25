<script setup>
import Toast from 'primevue/toast';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppTopbar from './Partials/AppTopbar.vue';
import AppSidebar from './Partials/AppSidebar.vue';
import { useLayout } from '@/Composables/useLayout';

defineProps({
    title: { type: String, default: '' },
});

const { containerClass, closeMobileSidebar } = useLayout();
const page = usePage();

watch(
    () => page.url,
    () => closeMobileSidebar(),
);
</script>

<template>
    <div class="app-shell" :class="containerClass">
        <AppTopbar />
        <AppSidebar />

        <div class="app-sidebar-mask" @click="closeMobileSidebar" />

        <main class="app-content">
            <h1 v-if="title" class="text-xl font-semibold text-surface-900 mb-4">{{ title }}</h1>
            <slot />
        </main>

        <Toast />
    </div>
</template>
