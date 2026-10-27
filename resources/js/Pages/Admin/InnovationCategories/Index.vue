<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import ActionsMenu from '@/Components/ActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const applySearch = () => {
    router.get(route('admin.innovation-categories.index'), {
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
    router.get(route('admin.innovation-categories.index'), {
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
    router.get(route('admin.innovation-categories.index'), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const menuItems = (category) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.innovation-categories.edit', category.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        disabled: !category.can_delete,
        command: () => remove(category),
    },
];

const remove = (category) => {
    confirm.require({
        message: `Hapus kategori "${category.name}"?`,
        header: 'Hapus Kategori',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.innovation-categories.destroy', category.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Kategori Inovasi</h1>
                <p class="text-sm text-surface-500">Kelompok inovasi, termasuk subkategori.</p>
            </div>
            <Button
                label="Tambah Kategori"
                icon="pi pi-plus"
                @click="router.visit(route('admin.innovation-categories.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari nama kategori" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="categories.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column field="name" header="Nama" sortable />
                <Column header="Induk">
                    <template #body="{ data }">{{ data.parent?.name ?? '—' }}</template>
                </Column>
                <Column field="icon" header="Ikon" sortable>
                    <template #body="{ data }">
                        <span v-if="data.icon" class="inline-flex items-center gap-2 text-sm text-surface-700">
                            <i :class="data.icon" />
                            {{ data.icon }}
                        </span>
                        <span v-else class="text-surface-400">—</span>
                    </template>
                </Column>
                <Column field="children_count" header="Subkategori" sortable />
                <Column field="usage_count" header="Dipakai" sortable />
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada kategori.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="categories.total > 0"
                class="mt-4"
                :rows="categories.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="categories.total"
                :first="(categories.current_page - 1) * categories.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
