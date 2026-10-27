<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import { PageFlip } from 'page-flip';
import ProgressSpinner from 'primevue/progressspinner';
import Button from 'primevue/button';

// Served as a static public asset (kept in sync via the npm "postinstall" script)
// because resolving the pdf.js worker through Vite's module graph is unreliable.
pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.min.mjs';

const props = defineProps({
    url: { type: String, required: true },
});

const container = ref(null);
const loading = ref(true);
const error = ref(null);
const pageCount = ref(0);
const currentPage = ref(1);

let pageFlip = null;

const renderPages = async () => {
    loading.value = true;
    error.value = null;

    try {
        const pdf = await pdfjsLib.getDocument({ url: props.url }).promise;
        const images = [];

        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
            const page = await pdf.getPage(pageNumber);
            const viewport = page.getViewport({ scale: 1.5 });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: canvas.getContext('2d'), canvas, viewport }).promise;

            images.push(canvas.toDataURL('image/jpeg', 0.85));
        }

        pageCount.value = images.length;

        pageFlip = new PageFlip(container.value, {
            width: 420,
            height: 594,
            size: 'stretch',
            minWidth: 280,
            maxWidth: 900,
            minHeight: 400,
            maxHeight: 1270,
            showCover: true,
            maxShadowOpacity: 0.5,
        });

        pageFlip.loadFromImages(images);
        pageFlip.on('flip', (event) => {
            currentPage.value = event.data + 1;
        });
    } catch (e) {
        console.error('PdfFlipbook render error:', e);
        error.value = 'Gagal memuat dokumen PDF.';
    } finally {
        loading.value = false;
    }
};

const goPrev = () => pageFlip?.flipPrev();
const goNext = () => pageFlip?.flipNext();

onMounted(renderPages);

watch(() => props.url, () => {
    pageFlip?.destroy();
    pageFlip = null;
    renderPages();
});

onBeforeUnmount(() => {
    pageFlip?.destroy();
    pageFlip = null;
});
</script>

<template>
    <div class="flex flex-col items-center gap-4">
        <div v-if="loading" class="flex flex-col items-center gap-3 py-16">
            <ProgressSpinner style="width: 3rem; height: 3rem" stroke-width="4" />
            <p class="text-sm text-surface-500">Memuat halaman dokumen...</p>
        </div>

        <p v-else-if="error" class="text-sm text-rose-600 py-16">{{ error }}</p>

        <div v-show="!loading && !error" ref="container" class="w-full" />

        <div v-if="!loading && !error" class="flex items-center gap-3">
            <Button icon="pi pi-chevron-left" severity="secondary" text rounded aria-label="Sebelumnya" @click="goPrev" />
            <span class="text-sm text-surface-500">Halaman {{ currentPage }} / {{ pageCount }}</span>
            <Button icon="pi pi-chevron-right" severity="secondary" text rounded aria-label="Berikutnya" @click="goNext" />
        </div>
    </div>
</template>
