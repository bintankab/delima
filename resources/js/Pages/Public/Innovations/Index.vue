<script setup>
import { router } from '@inertiajs/vue3';
import Paginator from 'primevue/paginator';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    innovations: { type: Object, required: true },
});

const jenisLabel = (jenis) => ({
    produk: 'Produk',
    proses: 'Proses',
    pelayanan: 'Pelayanan',
}[jenis] ?? jenis);

const onPage = (event) => {
    router.get(route('inovasi'), { page: event.page + 1 }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-2xl font-bold">Inovasi</h1>
            <p class="mt-2 text-indigo-100 max-w-2xl">
                Etalase inovasi daerah Kabupaten Bintan.
            </p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <article
                v-for="innovation in innovations.data"
                :key="innovation.id"
                class="bg-white rounded-xl border border-surface-200 overflow-hidden flex flex-col"
            >
                <div class="aspect-[4/3] bg-surface-100 overflow-hidden">
                    <img
                        :src="innovation.cover || '/images/placeholder-cover.svg'"
                        :alt="innovation.title"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="p-4 flex flex-col gap-2 flex-1">
                    <div class="flex items-center gap-2 text-xs text-surface-500">
                        <span v-if="innovation.category">{{ innovation.category }}</span>
                        <span v-if="innovation.category">&middot;</span>
                        <span>{{ jenisLabel(innovation.jenis_inovasi) }}</span>
                        <span>&middot;</span>
                        <span>{{ innovation.tahun }}</span>
                    </div>
                    <h2 class="font-semibold text-surface-900 leading-snug">{{ innovation.title }}</h2>
                    <p class="text-sm text-surface-600 flex-1">{{ innovation.description }}</p>
                    <p v-if="innovation.organisasi" class="text-xs text-surface-400 mt-1">{{ innovation.organisasi }}</p>
                </div>
            </article>

            <p v-if="!innovations.data.length" class="text-surface-400 text-sm col-span-full text-center py-10">
                Belum ada inovasi yang dipublikasikan.
            </p>
        </div>

        <Paginator
            v-if="innovations.last_page > 1"
            class="mt-8"
            :rows="innovations.per_page"
            :total-records="innovations.total"
            :first="(innovations.current_page - 1) * innovations.per_page"
            @page="onPage"
        />
    </section>
</template>
