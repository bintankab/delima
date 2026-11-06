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
    faqs: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const applySearch = () => {
    router.get(route('admin.faqs.index'), {
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
    router.get(route('admin.faqs.index'), {
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
    router.get(route('admin.faqs.index'), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const menuItems = (faq) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.faqs.edit', faq.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        command: () => remove(faq),
    },
];

const remove = (faq) => {
    confirm.require({
        message: `Hapus pertanyaan "${faq.question}"?`,
        header: 'Hapus FAQ',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.faqs.destroy', faq.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-white">FAQ</h1>
                <p class="text-sm text-white/70">Pertanyaan yang tampil di halaman publik /faq.</p>
            </div>
            <Button
                label="Tambah FAQ"
                icon="pi pi-plus"
                @click="router.visit(route('admin.faqs.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari pertanyaan, jawaban, atau kategori" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="faqs.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column header="No" style="width: 3.5rem">
                    <template #body="{ index }">{{ (faqs.current_page - 1) * faqs.per_page + index + 1 }}</template>
                </Column>
                <Column field="question" header="Pertanyaan" sortable />
                <Column field="category" header="Kategori" sortable>
                    <template #body="{ data }">{{ data.category ?? '—' }}</template>
                </Column>
                <Column field="order" header="Urutan" sortable style="width: 6rem" />
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada FAQ.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="faqs.total > 0"
                class="mt-4"
                :rows="faqs.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="faqs.total"
                :first="(faqs.current_page - 1) * faqs.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
