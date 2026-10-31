<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    documents: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? null);
const tahun = ref(props.filters.tahun ?? null);
const organisasiId = ref(props.filters.organisasi_id ?? null);
const loading = ref(false);

const hasActiveFilters = () => search.value !== '' || type.value !== null || tahun.value !== null || organisasiId.value !== null;

const applyFilters = () => {
    router.get(route('pustaka'), {
        search: search.value,
        type: type.value,
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
    type.value = null;
    tahun.value = null;
    organisasiId.value = null;
    applyFilters();
};

const onPage = (event) => {
    router.get(route('pustaka'), {
        search: props.filters.search,
        type: props.filters.type,
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

const typeSeverity = {
    regulasi: 'info',
    panduan: 'success',
    laporan: 'warn',
    lainnya: 'secondary',
};
</script>

<template>
    <section class="bg-indigo-700 text-white">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-2xl font-bold">Pustaka</h1>
            <p class="mt-2 text-indigo-100 max-w-2xl">
                Dokumen publik Kabupaten Bintan: regulasi, panduan, laporan, dan arsip lainnya.
            </p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-10">
        <form
            class="bg-white rounded-xl border border-surface-200 p-5 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end"
            @submit.prevent="applyFilters"
        >
            <div class="flex flex-col gap-1.5 lg:col-span-2">
                <label for="search" class="text-sm font-medium text-surface-700">Judul / Nomor Dokumen</label>
                <InputText id="search" v-model="search" placeholder="Cari dokumen" class="w-full" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="type" class="text-sm font-medium text-surface-700">Jenis</label>
                <Select
                    input-id="type"
                    v-model="type"
                    :options="filterOptions.types"
                    option-label="name"
                    option-value="id"
                    placeholder="Semua jenis"
                    show-clear
                    class="w-full"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="tahun" class="text-sm font-medium text-surface-700">Tahun Terbit</label>
                <Select
                    input-id="tahun"
                    v-model="tahun"
                    :options="filterOptions.years"
                    placeholder="Semua tahun"
                    show-clear
                    class="w-full"
                />
            </div>

            <div class="flex flex-col gap-1.5 lg:col-span-2">
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

            <div class="flex gap-2 lg:col-span-2">
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

        <div class="flex flex-col gap-3">
            <div
                v-for="document in documents.data"
                :key="document.id"
                class="bg-white rounded-xl border border-surface-200 p-4 flex items-start gap-4 hover:border-indigo-300 hover:shadow-sm transition"
            >
                <div class="w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i class="pi pi-file-pdf text-xl" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <Tag :value="document.type_label" :severity="typeSeverity[document.type] ?? 'secondary'" class="text-xs" />
                        <span v-if="document.nomor_dokumen" class="text-xs text-surface-500">{{ document.nomor_dokumen }}</span>
                    </div>
                    <h2 class="font-semibold text-surface-900 leading-snug">{{ document.title }}</h2>
                    <p v-if="document.description" class="text-sm text-surface-600 mt-1">{{ document.description }}</p>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-surface-500 mt-2">
                        <span v-if="document.organisasi">{{ document.organisasi }}</span>
                        <span v-if="document.penyusun">Disusun oleh {{ document.penyusun }}</span>
                        <span v-if="document.tahun_terbit">Terbit {{ document.tahun_terbit }}</span>
                        <span v-if="document.file_size">{{ document.file_size }}</span>
                    </div>
                </div>
                <a
                    :href="document.download_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 shrink-0"
                >
                    <i class="pi pi-download" /> Unduh
                </a>
            </div>

            <p v-if="!documents.data.length" class="text-surface-400 text-sm text-center py-10">
                {{ hasActiveFilters()
                    ? 'Tidak ada dokumen yang cocok dengan filter yang dipilih.'
                    : 'Belum ada dokumen yang dipublikasikan.' }}
            </p>
        </div>

        <Paginator
            v-if="documents.last_page > 1"
            class="mt-8"
            :rows="documents.per_page"
            :total-records="documents.total"
            :first="(documents.current_page - 1) * documents.per_page"
            @page="onPage"
        />
    </section>
</template>
