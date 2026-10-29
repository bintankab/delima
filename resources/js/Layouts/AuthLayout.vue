<script setup>
import Toast from 'primevue/toast';
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-surface-100 px-4">
        <Toast />
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2 text-indigo-700 font-bold text-2xl">
                    <i class="pi pi-sitemap" />
                    <span>DELIMA</span>
                </div>
                <p class="text-surface-500 text-sm mt-1">Simpul Jaring Inovasi Daerah</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-surface-200 p-6">
                <slot />
            </div>
        </div>
    </div>
</template>
