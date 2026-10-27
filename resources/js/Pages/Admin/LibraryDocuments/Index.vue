<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import ActionsMenu from '@/Components/ActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    documents: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const typeLabel = (type) => ({
    regulasi: 'Regulasi',
    panduan: 'Panduan',
    laporan: 'Laporan',
    lainnya: 'Lainnya',
}[type] ?? type ?? '—');

const applySearch = () => {
    router.get(route('admin.library-documents.index'), {
        search: search.value,
        per_page: props.filters.per_page,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    router.get(route('admin.library-documents.index'), {
        search: props.filters.search,
        page: event.page + 1,
        per_page: event.rows,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onSort = (event) => {
    router.get(route('admin.library-documents.index'), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const remove = (document) => {
    confirm.require({
        message: `Hapus dokumen "${document.title}"?`,
        header: 'Hapus Dokumen',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.library-documents.destroy', document.id)),
    });
};

const menuItems = (document) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.library-documents.edit', document.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        command: () => remove(document),
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Pustaka</h1>
                <p class="text-sm text-surface-500">Dokumen non-riset dan non-inovasi: peraturan, laporan statistik, laporan BPS, dan lainnya.</p>
            </div>
            <Button
                label="Tambah Dokumen"
                icon="pi pi-plus"
                @click="router.visit(route('admin.library-documents.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari judul atau nomor dokumen" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="documents.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column field="title" header="Judul" sortable />
                <Column field="type" header="Jenis" sortable>
                    <template #body="{ data }">{{ typeLabel(data.type) }}</template>
                </Column>
                <Column header="Organisasi/OPD">
                    <template #body="{ data }">{{ data.organisasi ?? '—' }}</template>
                </Column>
                <Column field="tahun_terbit" header="Tahun Terbit" sortable>
                    <template #body="{ data }">{{ data.tahun_terbit ?? '—' }}</template>
                </Column>
                <Column field="is_public" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_public ? 'Publik' : 'Privat'"
                            :severity="data.is_public ? 'success' : 'secondary'"
                        />
                    </template>
                </Column>
                <Column field="downloads_count" header="Unduhan" sortable />
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada dokumen.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="documents.total > 0"
                class="mt-4"
                :rows="documents.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="documents.total"
                :first="(documents.current_page - 1) * documents.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
