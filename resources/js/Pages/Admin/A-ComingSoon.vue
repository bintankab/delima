<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <div class="bg-white rounded-xl border border-surface-200 p-10 text-center">
        <i class="pi pi-hammer text-4xl text-indigo-400 mb-3" />
        <h2 class="text-lg font-semibold text-surface-900">{{ title }}</h2>
        <p class="text-surface-500 text-sm mt-1">Modul ini sedang dalam pengembangan bertahap.</p>
    </div>
</template>
