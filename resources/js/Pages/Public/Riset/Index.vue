<script setup>
import { router } from '@inertiajs/vue3';
import Paginator from 'primevue/paginator';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    risets: { type: Object, required: true },
});

const onPage = (event) => {
    router.get(route('riset'), { page: event.page + 1 }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-2xl font-bold">Riset</h1>
            <p class="mt-2 text-indigo-100 max-w-2xl">
                Dokumentasi hasil penelitian dan kajian daerah Kabupaten Bintan.
            </p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <article
                v-for="riset in risets.data"
                :key="riset.id"
                class="bg-white rounded-xl border border-surface-200 overflow-hidden flex flex-col"
            >
                <div class="aspect-[4/3] bg-surface-100 overflow-hidden">
                    <img
                        :src="riset.cover || '/images/placeholder-cover.svg'"
                        :alt="riset.title"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="p-4 flex flex-col gap-2 flex-1">
                    <div class="flex items-center gap-2 text-xs text-surface-500">
                        <span v-if="riset.category">{{ riset.category }}</span>
                        <span v-if="riset.category">&middot;</span>
                        <span>{{ riset.tahun }}</span>
                    </div>
                    <h2 class="font-semibold text-surface-900 leading-snug">{{ riset.title }}</h2>
                    <p class="text-sm text-surface-600 flex-1">{{ riset.abstrak }}</p>
                    <p v-if="riset.organisasi" class="text-xs text-surface-400 mt-1">{{ riset.organisasi }}</p>
                </div>
            </article>

            <p v-if="!risets.data.length" class="text-surface-400 text-sm col-span-full text-center py-10">
                Belum ada riset yang dipublikasikan.
            </p>
        </div>

        <Paginator
            v-if="risets.last_page > 1"
            class="mt-8"
            :rows="risets.per_page"
            :total-records="risets.total"
            :first="(risets.current_page - 1) * risets.per_page"
            @page="onPage"
        />
    </section>
</template>
