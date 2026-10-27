<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import PdfFlipbook from '@/Components/PdfFlipbook.vue';

defineProps({
    documents: { type: Array, required: true },
});

const activeDocument = ref(null);

const isPdf = (document) => document.name.toLowerCase().endsWith('.pdf');

const openFlipbook = (document) => {
    activeDocument.value = document;
};
</script>

<template>
    <ul class="flex flex-col gap-2">
        <li
            v-for="document in documents"
            :key="document.id"
            class="flex items-center justify-between gap-2 text-sm bg-surface-50 rounded-lg px-3 py-2"
        >
            <span class="flex items-center gap-2 text-surface-700 min-w-0">
                <i class="pi pi-file" />
                <span class="truncate">{{ document.name }}</span>
                <span class="text-surface-400 shrink-0">({{ document.size }})</span>
            </span>
            <span class="flex items-center gap-3 shrink-0">
                <button
                    v-if="isPdf(document)"
                    type="button"
                    class="text-indigo-600 font-medium"
                    @click="openFlipbook(document)"
                >
                    <i class="pi pi-book" /> Baca
                </button>
                <Link v-if="isPdf(document) && document.read_url" :href="document.read_url" class="text-indigo-600 font-medium">
                    <i class="pi pi-window-maximize" /> Layar penuh
                </Link>
                <a :href="document.url" target="_blank" rel="noopener" class="text-indigo-600 font-medium">
                    <i class="pi pi-download" /> Unduh
                </a>
            </span>
        </li>
    </ul>

    <Dialog
        :visible="activeDocument !== null"
        modal
        :style="{ width: '48rem' }"
        @update:visible="activeDocument = null"
    >
        <template #header>
            <div class="flex items-center gap-3 min-w-0">
                <span class="font-semibold truncate">{{ activeDocument?.name }}</span>
                <Link
                    v-if="activeDocument?.read_url"
                    :href="activeDocument.read_url"
                    class="text-indigo-600 text-sm font-medium shrink-0"
                >
                    <i class="pi pi-window-maximize" /> Layar penuh
                </Link>
            </div>
        </template>
        <PdfFlipbook v-if="activeDocument" :url="activeDocument.url" />
    </Dialog>
</template>
