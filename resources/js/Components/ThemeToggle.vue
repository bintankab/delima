<script setup>
import { usePublicTheme } from '@/Composables/usePublicTheme';

const { isDark, toggleTheme } = usePublicTheme();
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center justify-center w-9 h-9 rounded-lg cursor-pointer text-[#FFD700] bg-white/10 hover:bg-white/20"
        :aria-label="isDark ? 'Gunakan mode terang' : 'Gunakan mode gelap'"
        :title="isDark ? 'Mode terang' : 'Mode gelap'"
        @click="toggleTheme"
    >
        <i :class="isDark ? 'pi pi-sun' : 'pi pi-moon'" />
    </button>
</template>
