<script setup>
import { Link } from '@inertiajs/vue3';
import PdfFlipbook from '@/Components/PdfFlipbook.vue';

defineProps({
    document: { type: Object, required: true },
    back: { type: Object, required: true },
});
</script>

<template>
    <div class="min-h-screen bg-surface-900 flex flex-col">
        <header class="flex items-center gap-4 px-4 h-14 bg-surface-950 shrink-0">
            <Link :href="back.url" class="flex items-center gap-2 text-sm text-surface-200 hover:text-white shrink-0">
                <i class="pi pi-arrow-left" />
                Kembali ke {{ back.label }}
            </Link>
            <span class="text-sm text-surface-400 truncate ml-auto">{{ document.name }}</span>
        </header>

        <main class="flex-1 min-h-0 p-4 overflow-auto flex flex-col">
            <div class="flex-1 min-h-0 w-full max-w-[1400px] mx-auto flex flex-col">
                <PdfFlipbook :url="document.url" :max-width="1400" :max-height="1900" />
            </div>
        </main>
    </div>
</template>
