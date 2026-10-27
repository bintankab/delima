<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import { PageFlip } from '@/vendor/page-flip.js';
import ProgressSpinner from 'primevue/progressspinner';
import Button from 'primevue/button';

// Served as a static public asset (kept in sync via the npm "postinstall" script)
// because resolving the pdf.js worker through Vite's module graph is unreliable.
pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.min.mjs';

const props = defineProps({
    url: { type: String, required: true },
    maxWidth: { type: Number, default: 900 },
    maxHeight: { type: Number, default: 1270 },
});

const container = ref(null);
const loading = ref(true);
const error = ref(null);
const pageCount = ref(0);
const currentPage = ref(1);

// Target a fixed output width regardless of the page's point size, so
// dense/landscape pages (e.g. wide tables) get enough pixels to stay
// legible instead of being scaled down to the flipbook's display size.
const dprFactor = Math.min(window.devicePixelRatio || 1, 2);
const TARGET_WIDTH = 2800 * dprFactor;

const BLANK_PAGE = `data:image/svg+xml;utf8,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="420" height="594"><rect width="420" height="594" fill="#f8fafc"/></svg>',
)}`;

let pageFlip = null;
let pdfDoc = null;
let images = [];
let pending = [];
let renderToken = 0;
const renderedPages = new Set();

const computeScale = (page) => {
    const baseWidth = page.getViewport({ scale: 1 }).width;

    return Math.min(Math.max(TARGET_WIDTH / baseWidth, 1.2), 6);
};

const renderPage = async (pageNumber) => {
    const page = await pdfDoc.getPage(pageNumber);
    const viewport = page.getViewport({ scale: computeScale(page) });

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({ canvasContext: canvas.getContext('2d'), canvas, viewport }).promise;

    return canvas.toDataURL('image/jpeg', 0.95);
};

// Moves a not-yet-rendered page to the front of the background render
// queue, so pages the reader actually flips to jump ahead of the rest.
const prioritize = (pageNumber) => {
    const index = pending.indexOf(pageNumber);

    if (index > 0) {
        pending.splice(index, 1);
        pending.unshift(pageNumber);
    }
};

const processQueue = async (token) => {
    while (pending.length) {
        if (token !== renderToken) {
            return;
        }

        const pageNumber = pending.shift();

        if (renderedPages.has(pageNumber)) {
            continue;
        }

        images[pageNumber - 1] = await renderPage(pageNumber);
        renderedPages.add(pageNumber);

        if (token !== renderToken) {
            return;
        }

        pageFlip?.updateFromImages(images);
    }
};

const renderPages = async () => {
    loading.value = true;
    error.value = null;

    pageFlip?.destroy();
    pageFlip = null;

    const token = ++renderToken;
    renderedPages.clear();
    pending = [];

    try {
        pdfDoc = await pdfjsLib.getDocument({ url: props.url }).promise;

        if (token !== renderToken) {
            return;
        }

        const total = pdfDoc.numPages;
        pageCount.value = total;
        images = new Array(total).fill(BLANK_PAGE);

        // Render just the opening spread up front so the flipbook appears
        // quickly; the rest streams in afterwards instead of blocking here.
        for (const pageNumber of total >= 2 ? [1, 2] : [1]) {
            images[pageNumber - 1] = await renderPage(pageNumber);
            renderedPages.add(pageNumber);
        }

        if (token !== renderToken) {
            return;
        }

        pageFlip = new PageFlip(container.value, {
            width: 420,
            height: 594,
            size: 'stretch',
            minWidth: 280,
            maxWidth: props.maxWidth,
            minHeight: 400,
            maxHeight: props.maxHeight,
            showCover: true,
            maxShadowOpacity: 0.5,
        });

        pageFlip.loadFromImages(images);
        pageFlip.on('flip', (event) => {
            currentPage.value = event.data + 1;

            [-1, 0, 1, 2]
                .map((offset) => currentPage.value + offset)
                .filter((pageNumber) => pageNumber >= 1 && pageNumber <= total && !renderedPages.has(pageNumber))
                .forEach(prioritize);
        });

        loading.value = false;

        pending = Array.from({ length: total }, (_, index) => index + 1).filter((pageNumber) => !renderedPages.has(pageNumber));
        await processQueue(token);
    } catch (e) {
        if (token !== renderToken) {
            return;
        }

        console.error('PdfFlipbook render error:', e);
        error.value = 'Gagal memuat dokumen PDF.';
        loading.value = false;
    }
};

const goPrev = () => pageFlip?.flipPrev();
const goNext = () => pageFlip?.flipNext();

onMounted(renderPages);

watch(() => props.url, renderPages);

onBeforeUnmount(() => {
    renderToken += 1;
    pageFlip?.destroy();
    pageFlip = null;
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 w-full flex-1 min-h-0">
        <div v-if="loading" class="flex flex-col items-center gap-3 py-16">
            <ProgressSpinner style="width: 3rem; height: 3rem" stroke-width="4" />
            <p class="text-sm text-surface-500">Memuat halaman dokumen...</p>
        </div>

        <p v-else-if="error" class="text-sm text-rose-600 py-16">{{ error }}</p>

        <div v-show="!loading && !error" ref="container" class="w-full flex-1 min-h-0" />

        <div v-if="!loading && !error" class="flex items-center gap-3">
            <Button icon="pi pi-chevron-left" severity="secondary" text rounded aria-label="Sebelumnya" @click="goPrev" />
            <span class="text-sm text-surface-500">Halaman {{ currentPage }} / {{ pageCount }}</span>
            <Button icon="pi pi-chevron-right" severity="secondary" text rounded aria-label="Berikutnya" @click="goNext" />
        </div>
    </div>
</template>
