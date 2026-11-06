<script setup>
import Toast from 'primevue/toast';
import PublicTopbar from './Partials/PublicTopbar.vue';
</script>

<template>
    <div class="min-h-screen bg-[#494a4d] flex flex-col">
        <Toast />

        <PublicTopbar />

        <main class="flex-1">
            <slot />
        </main>

        <footer class="bg-surface-900 text-surface-300 text-sm py-8 mt-10">
            <div class="max-w-6xl mx-auto px-4">
                <p class="font-semibold text-white">DELIMA — Simpul Jaring Inovasi Daerah</p>
                <p class="mt-1 text-surface-400">
                    Dokumentasi, diseminasi, dan replikasi inovasi daerah Kabupaten Bintan.
                </p>
            </div>
        </footer>
    </div>
</template>
