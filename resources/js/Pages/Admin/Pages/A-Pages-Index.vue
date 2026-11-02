<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ActionsMenu from '@/Components/ActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    pages: { type: Object, required: true },
    filters: { type: Object, required: true },
    statuses: { type: Array, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);

const query = (extra = {}) => ({
    search: props.filters.search,
    status: props.filters.status,
    per_page: props.filters.per_page,
    sort: props.filters.sort,
    direction: props.filters.direction,
    ...extra,
});

const applySearch = () => {
    router.get(route('admin.pages.index'), query({
        search: search.value,
        status: status.value,
        page: 1,
    }), {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    router.get(route('admin.pages.index'), query({
        page: event.page + 1,
        per_page: event.rows,
    }), {
        preserveState: true,
        replace: true,
    });
};

const onSort = (event) => {
    router.get(route('admin.pages.index'), query({
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }), {
        preserveState: true,
        replace: true,
    });
};

const remove = (item) => {
    confirm.require({
        message: `Pindahkan page "${item.title}" ke sampah?`,
        header: 'Hapus Page',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.pages.destroy', item.id)),
    });
};

const menuItems = (item) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.pages.edit', item.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        command: () => remove(item),
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Page</h1>
                <p class="text-sm text-surface-500">Halaman statis. Setiap halaman memilih satu layout dan menyimpan isinya sendiri.</p>
            </div>
            <Button
                label="Tulis Page"
                icon="pi pi-plus"
                @click="router.visit(route('admin.pages.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex flex-wrap gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari judul page" class="w-full sm:max-w-xs" />
                <Select
                    v-model="status"
                    :options="statuses"
                    option-label="name"
                    option-value="id"
                    placeholder="Semua status"
                    show-clear
                    class="w-full sm:w-48"
                />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="pages.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column header="No" style="width: 3.5rem">
                    <template #body="{ index }">{{ (pages.current_page - 1) * pages.per_page + index + 1 }}</template>
                </Column>
                <Column field="title" header="Judul" sortable />
                <Column field="layout" header="Layout" sortable>
                    <template #body="{ data }">{{ data.layout_label }}</template>
                </Column>
                <Column field="status" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag
                            :value="data.status_label"
                            :severity="data.status === 'published' ? 'success' : 'secondary'"
                        />
                    </template>
                </Column>
                <Column field="published_at" header="Terbit" sortable>
                    <template #body="{ data }">{{ data.published_at ?? '—' }}</template>
                </Column>
                <Column field="views_count" header="Tayangan" sortable />
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada page.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="pages.total > 0"
                class="mt-4"
                :rows="pages.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="pages.total"
                :first="(pages.current_page - 1) * pages.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
