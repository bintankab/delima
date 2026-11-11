<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import Toast from 'primevue/toast';

onMounted(() => document.documentElement.classList.add('app-dark'));
onBeforeUnmount(() => document.documentElement.classList.remove('app-dark'));
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-black px-4">
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
