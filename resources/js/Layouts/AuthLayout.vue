<script setup>
import Toast from 'primevue/toast';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { usePublicTheme } from '@/Composables/usePublicTheme';

usePublicTheme();
</script>

<template>
    <div class="public-theme relative min-h-screen flex items-center justify-center bg-black px-4">
        <div class="absolute top-4 right-4">
            <ThemeToggle />
        </div>
        <Toast />
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3 text-[#FFD700] font-bold text-2xl">
                    <img src="/images/logo-2.png" alt="" class="h-14 w-auto" />
                    <span>DELIMA</span>
                </div>
                <p class="text-white/70 text-sm mt-1">Simpul Jaring Inovasi Daerah</p>
            </div>
            <div class="bg-neutral-900 rounded-xl shadow-sm border border-neutral-800 p-6">
                <slot />
            </div>
        </div>
    </div>
</template>
