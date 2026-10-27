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
    organisasis: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const applySearch = () => {
    router.get(route('admin.organisasis.index'), {
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
    router.get(route('admin.organisasis.index'), {
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
    router.get(route('admin.organisasis.index'), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const canDelete = (organisasi) => organisasi.can_delete && organisasi.documents_count <= 1;

const menuItems = (organisasi) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.organisasis.edit', organisasi.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        disabled: !canDelete(organisasi),
        command: () => remove(organisasi),
    },
];

const remove = (organisasi) => {
    confirm.require({
        message: `Hapus organisasi "${organisasi.name}"?`,
        header: 'Hapus Organisasi',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.organisasis.destroy', organisasi.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Organisasi/OPD</h1>
                <p class="text-sm text-surface-500">Daftar organisasi perangkat daerah.</p>
            </div>
            <Button
                label="Tambah Organisasi"
                icon="pi pi-plus"
                @click="router.visit(route('admin.organisasis.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari nama organisasi" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="organisasis.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column header="Logo" style="width: 4rem">
                    <template #body="{ data }">
                        <img
                            v-if="data.logo_thumb"
                            :src="data.logo_thumb"
                            alt="Logo"
                            class="w-10 h-10 rounded-lg object-cover border border-surface-200"
                        />
                        <span v-else class="text-surface-400">—</span>
                    </template>
                </Column>
                <Column field="name" header="Nama" sortable />
                <Column field="type" header="Jenis" sortable>
                    <template #body="{ data }">{{ data.type ?? '—' }}</template>
                </Column>
                <Column field="code" header="Kode" sortable>
                    <template #body="{ data }">{{ data.code ?? '—' }}</template>
                </Column>
                <Column field="contact_person" header="Kontak" sortable>
                    <template #body="{ data }">
                        <div class="text-sm">
                            <div>{{ data.contact_person ?? '—' }}</div>
                            <div class="text-surface-400">{{ data.phone ?? data.email ?? '' }}</div>
                        </div>
                    </template>
                </Column>
                <Column field="is_active" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                        />
                    </template>
                </Column>
                <Column
                    field="documents_count"
                    sort-field="library_documents_count"
                    header="Jumlah Dokumen"
                    sortable
                />
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada organisasi.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="organisasis.total > 0"
                class="mt-4"
                :rows="organisasis.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="organisasis.total"
                :first="(organisasis.current_page - 1) * organisasis.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
