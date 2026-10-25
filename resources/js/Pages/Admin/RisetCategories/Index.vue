<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const applySearch = () => {
    router.get(route('admin.riset-categories.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    router.get(route('admin.riset-categories.index'), {
        search: props.filters.search,
        page: event.page + 1,
    }, {
        preserveState: true,
        replace: true,
    });
};

const remove = (category) => {
    confirm.require({
        message: `Hapus kategori "${category.name}"?`,
        header: 'Hapus Kategori',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.riset-categories.destroy', category.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Kategori Riset</h1>
                <p class="text-sm text-surface-500">Kelompok riset, termasuk subkategori.</p>
            </div>
            <Button
                label="Tambah Kategori"
                icon="pi pi-plus"
                @click="router.visit(route('admin.riset-categories.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari nama kategori" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable :value="categories.data" size="small">
                <Column field="name" header="Nama" />
                <Column header="Induk">
                    <template #body="{ data }">{{ data.parent?.name ?? '—' }}</template>
                </Column>
                <Column header="Ikon">
                    <template #body="{ data }">
                        <span v-if="data.icon" class="inline-flex items-center gap-2 text-sm text-surface-700">
                            <i :class="data.icon" />
                            {{ data.icon }}
                        </span>
                        <span v-else class="text-surface-400">—</span>
                    </template>
                </Column>
                <Column field="children_count" header="Subkategori" />
                <Column field="usage_count" header="Dipakai" />
                <Column header="Aksi" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                aria-label="Ubah"
                                @click="router.visit(route('admin.riset-categories.edit', data.id))"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                aria-label="Hapus"
                                :disabled="!data.can_delete"
                                :title="data.can_delete ? 'Hapus' : 'Kategori masih memiliki subkategori atau data terkait.'"
                                @click="remove(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada kategori.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="categories.last_page > 1"
                class="mt-4"
                :rows="categories.per_page"
                :total-records="categories.total"
                :first="(categories.current_page - 1) * categories.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
