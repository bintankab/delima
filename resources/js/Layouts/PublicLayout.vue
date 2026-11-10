<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import Toast from 'primevue/toast';
import PublicTopbar from './Partials/PublicTopbar.vue';

onMounted(() => document.documentElement.classList.add('app-dark'));
onBeforeUnmount(() => document.documentElement.classList.remove('app-dark'));
</script>

<template>
    <div class="min-h-screen bg-black flex flex-col">
        <Toast />

        <PublicTopbar />

        <main class="flex-1">
            <slot />
        </main>

        <footer class="bg-black border-t border-neutral-800 text-neutral-400 text-sm py-8 mt-10">
            <div class="max-w-6xl mx-auto px-4">
                <p class="font-semibold text-[#FFD700]">DELIMA — Simpul Jaring Inovasi Daerah</p>
                <p class="mt-1 text-neutral-400">
                    Dokumentasi, diseminasi, dan replikasi inovasi daerah Kabupaten Bintan.
                </p>
            </div>
        </footer>
    </div>
</template>
