<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    latestNews: { type: Array, default: () => [] },
    topInnovations: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-6xl mx-auto px-4 py-16 text-center">
            <h1 class="text-3xl font-bold">Delima</h1>
            <p class="mt-3 text-indigo-100 max-w-2xl mx-auto">
                Pusat informasi dan pengetahuan inovasi daerah — dokumentasi, diseminasi, dan replikasi
                inovasi, serta platform interaksi dan mentoring antar stakeholder inovasi daerah.
            </p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-12">
        <h2 class="text-lg font-semibold text-surface-900 mb-4">Berita Terbaru</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <article
                v-for="item in latestNews"
                :key="item.id"
                class="bg-white rounded-xl border border-surface-200 p-4"
            >
                <h3 class="font-medium text-surface-900">{{ item.title }}</h3>
                <p class="text-sm text-surface-500 mt-1 line-clamp-2">{{ item.excerpt }}</p>
            </article>
            <p v-if="!latestNews.length" class="text-surface-400 text-sm">Belum ada berita.</p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-12">
        <h2 class="text-lg font-semibold text-surface-900 mb-4">Top Inovasi</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Link
                v-for="item in topInnovations"
                :key="item.id"
                :href="`/etalase/${item.slug}`"
                class="bg-white rounded-xl border border-surface-200 p-4 hover:border-indigo-300"
            >
                <h3 class="font-medium text-surface-900">{{ item.title }}</h3>
                <p class="text-sm text-surface-500 mt-1">Tahun {{ item.tahun }}</p>
            </Link>
            <p v-if="!topInnovations.length" class="text-surface-400 text-sm">Belum ada inovasi unggulan.</p>
        </div>
    </section>
</template>
