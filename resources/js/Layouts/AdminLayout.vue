<script setup>
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, watch } from 'vue';
import AppTopbar from './Partials/AppTopbar.vue';
import AppSidebar from './Partials/AppSidebar.vue';
import { useLayout } from '@/Composables/useLayout';

defineProps({
    title: { type: String, default: '' },
});

const { containerClass, closeMobileSidebar } = useLayout();
const page = usePage();
const toast = useToast();

watch(
    () => page.url,
    () => closeMobileSidebar(),
);

let stopListening;

onMounted(() => {
    stopListening = router.on('success', (event) => {
        const flash = event.detail.page.props.flash ?? {};

        if (flash.success) {
            toast.add({ severity: 'success', summary: 'Berhasil', detail: flash.success, life: 3000 });
        }

        if (flash.error) {
            toast.add({ severity: 'error', summary: 'Gagal', detail: flash.error, life: 4000 });
        }
    });
});

onUnmounted(() => {
    stopListening?.();
});
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
        <ConfirmDialog />
    </div>
</template>
