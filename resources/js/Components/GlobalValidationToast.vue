<script setup>
import { onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const page = usePage();

let removeError;

onMounted(() => {
    removeError = router.on('error', () => {
        if (page.component === 'Auth/Login') {
            return;
        }

        toast.add({
            severity: 'error',
            summary: 'Validasi gagal',
            detail: 'Periksa kembali isian formulir Anda.',
            life: 4000,
        });
    });
});

onUnmounted(() => {
    removeError?.();
});
</script>

<template></template>
