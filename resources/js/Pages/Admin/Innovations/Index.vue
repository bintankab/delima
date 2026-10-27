<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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
    innovations: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const confirm = useConfirm();
const isSuperAdmin = computed(() => (page.props.auth?.roles ?? []).includes('super-admin'));
const isTrashed = computed(() => !!props.filters.trashed);

const search = ref(props.filters.search ?? '');

const originLabel = (origin) => (origin === 'archive' ? 'Arsip' : 'Pengajuan');

const jenisLabel = (jenis) => ({
    produk: 'Produk',
    proses: 'Proses',
    pelayanan: 'Pelayanan',
}[jenis] ?? jenis ?? '—');

const statusLabel = (status) => ({
    draft: 'Draf',
    submitted: 'Diajukan',
    under_review: 'Ditinjau',
    reviewed: 'Direview',
    revision_needed: 'Perlu revisi',
    published: 'Tayang',
    rejected: 'Ditolak',
    archived: 'Diarsipkan',
}[status] ?? status);

const applySearch = () => {
    router.get(route('admin.innovations.index'), {
        search: search.value,
        trashed: isTrashed.value,
        per_page: props.filters.per_page,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    router.get(route('admin.innovations.index'), {
        search: props.filters.search,
        trashed: isTrashed.value,
        page: event.page + 1,
        per_page: event.rows,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const toggleTrashed = () => {
    router.get(route('admin.innovations.index'), {
        trashed: !isTrashed.value,
        per_page: props.filters.per_page,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onSort = (event) => {
    router.get(route('admin.innovations.index'), {
        search: props.filters.search,
        trashed: isTrashed.value,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const menuItems = (innovation) => {
    if (isTrashed.value) {
        return [
            { label: 'Hapus Permanen', icon: 'pi pi-trash', command: () => purge(innovation) },
        ];
    }

    const items = [
        { label: 'Lihat', icon: 'pi pi-eye', command: () => router.visit(route('admin.innovations.show', innovation.id)) },
    ];

    if (isSuperAdmin.value) {
        items.push(
            { label: 'Ubah', icon: 'pi pi-pencil', command: () => router.visit(route('admin.innovations.edit', innovation.id)) },
            { label: 'Hapus', icon: 'pi pi-trash', command: () => remove(innovation) },
        );
    }

    return items;
};

const remove = (innovation) => {
    confirm.require({
        message: `Hapus inovasi "${innovation.title}"?`,
        header: 'Hapus Inovasi',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.innovations.destroy', innovation.id)),
    });
};

const purge = (innovation) => {
    confirm.require({
        message: `Hapus permanen inovasi "${innovation.title}"? Cover dan dokumen yang sudah diunggah akan ikut terhapus dan tidak bisa dipulihkan lagi.`,
        header: 'Hapus Permanen',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus Permanen',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.innovations.force-destroy', innovation.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">
                    {{ isTrashed ? 'Sampah Inovasi' : 'Daftar Inovasi' }}
                </h1>
                <p class="text-sm text-surface-500">
                    {{ isTrashed
                        ? 'Inovasi yang sudah dihapus. Hapus permanen akan menghapus berkasnya juga.'
                        : 'Inovasi yang sudah masuk, termasuk arsip lama.' }}
                </p>
            </div>
            <Button
                v-if="isSuperAdmin"
                :label="isTrashed ? 'Kembali ke Daftar' : 'Tampilkan Sampah'"
                :icon="isTrashed ? 'pi pi-arrow-left' : 'pi pi-trash'"
                severity="secondary"
                outlined
                @click="toggleTrashed"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari judul inovasi" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="innovations.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column field="title" header="Judul" sortable />
                <Column field="jenis_inovasi" header="Jenis" sortable>
                    <template #body="{ data }">{{ jenisLabel(data.jenis_inovasi) }}</template>
                </Column>
                <Column header="OPD">
                    <template #body="{ data }">{{ data.organisasi ?? '—' }}</template>
                </Column>
                <Column header="Kategori">
                    <template #body="{ data }">{{ data.category ?? '—' }}</template>
                </Column>
                <Column field="tahun" header="Tahun" sortable />
                <Column field="published_at" header="Terbit" sortable>
                    <template #body="{ data }">{{ data.published_at ?? '—' }}</template>
                </Column>
                <Column field="status" header="Status" sortable>
                    <template #body="{ data }">{{ statusLabel(data.status) }}</template>
                </Column>
                <Column field="origin" header="Asal" sortable>
                    <template #body="{ data }">{{ originLabel(data.origin) }}</template>
                </Column>
                <Column v-if="isTrashed" field="deleted_at" header="Dihapus pada" sortable>
                    <template #body="{ data }">{{ data.deleted_at ?? '—' }}</template>
                </Column>
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">
                        {{ isTrashed ? 'Tidak ada inovasi di sampah.' : 'Belum ada inovasi.' }}
                    </span>
                </template>
            </DataTable>

            <Paginator
                v-if="innovations.total > 0"
                class="mt-4"
                :rows="innovations.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="innovations.total"
                :first="(innovations.current_page - 1) * innovations.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
