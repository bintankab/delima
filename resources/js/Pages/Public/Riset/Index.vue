<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Select from 'primevue/select';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    risets: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const tahun = ref(props.filters.tahun ?? null);
const organisasiId = ref(props.filters.organisasi_id ?? null);
const loading = ref(false);

const hasActiveFilters = () => search.value !== '' || tahun.value !== null || organisasiId.value !== null;

const applyFilters = () => {
    router.get(route('riset'), {
        search: search.value,
        tahun: tahun.value,
        organisasi_id: organisasiId.value,
    }, {
        preserveState: true,
        replace: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
};

const resetFilters = () => {
    search.value = '';
    tahun.value = null;
    organisasiId.value = null;
    applyFilters();
};

const onPage = (event) => {
    router.get(route('riset'), {
        search: props.filters.search,
        tahun: props.filters.tahun,
        organisasi_id: props.filters.organisasi_id,
        page: event.page + 1,
    }, {
        preserveState: true,
        replace: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
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
        <form
            class="bg-white rounded-xl border border-surface-200 p-5 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end"
            @submit.prevent="applyFilters"
        >
            <div class="flex flex-col gap-1.5 lg:col-span-2">
                <label for="search" class="text-sm font-medium text-surface-700">Judul</label>
                <InputText id="search" v-model="search" placeholder="Cari judul riset" class="w-full" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="tahun" class="text-sm font-medium text-surface-700">Tahun</label>
                <Select
                    input-id="tahun"
                    v-model="tahun"
                    :options="filterOptions.years"
                    placeholder="Semua tahun"
                    show-clear
                    class="w-full"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="organisasi_id" class="text-sm font-medium text-surface-700">Organisasi</label>
                <Select
                    input-id="organisasi_id"
                    v-model="organisasiId"
                    :options="filterOptions.organisasis"
                    option-label="name"
                    option-value="id"
                    placeholder="Semua organisasi"
                    show-clear
                    filter
                    class="w-full"
                />
            </div>

            <div class="flex gap-2 lg:col-span-4">
                <Button type="submit" label="Terapkan Filter" icon="pi pi-filter" :loading="loading" />
                <Button
                    v-if="hasActiveFilters()"
                    type="button"
                    label="Reset"
                    severity="secondary"
                    outlined
                    :disabled="loading"
                    @click="resetFilters"
                />
            </div>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <Link
                v-for="riset in risets.data"
                :key="riset.id"
                :href="route('riset.show', riset.slug)"
                class="bg-white rounded-xl border border-surface-200 overflow-hidden flex flex-col hover:border-indigo-300 hover:shadow-md transition"
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
            </Link>

            <p v-if="!risets.data.length" class="text-surface-400 text-sm col-span-full text-center py-10">
                {{ hasActiveFilters()
                    ? 'Tidak ada riset yang cocok dengan filter yang dipilih.'
                    : 'Belum ada riset yang dipublikasikan.' }}
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
