<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import DataView from 'primevue/dataview';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import ActionsMenu from '@/Components/ActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    risets: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const confirm = useConfirm();
const isSuperAdmin = computed(() => (page.props.auth?.roles ?? []).includes('super-admin'));
const isTrashed = computed(() => !!props.filters.trashed);

const search = ref(props.filters.search ?? '');

const originLabel = (origin) => (origin === 'archive' ? 'Arsip' : 'Pengajuan');

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

const statusSeverity = {
    draft: 'secondary',
    submitted: 'info',
    under_review: 'info',
    reviewed: 'warn',
    revision_needed: 'warn',
    published: 'success',
    rejected: 'danger',
    archived: 'contrast',
};

const applySearch = () => {
    router.get(route('admin.risets.index'), {
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
    router.get(route('admin.risets.index'), {
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
    router.get(route('admin.risets.index'), {
        trashed: !isTrashed.value,
        per_page: props.filters.per_page,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const menuItems = (riset) => {
    if (isTrashed.value) {
        return [
            { label: 'Hapus Permanen', icon: 'pi pi-trash', command: () => purge(riset) },
        ];
    }

    if (!isSuperAdmin.value) {
        return [];
    }

    return [
        { label: 'Ubah', icon: 'pi pi-pencil', command: () => router.visit(route('admin.risets.edit', riset.id)) },
        { label: 'Hapus', icon: 'pi pi-trash', command: () => remove(riset) },
    ];
};

const remove = (riset) => {
    confirm.require({
        message: `Hapus riset "${riset.title}"?`,
        header: 'Hapus Riset',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.risets.destroy', riset.id)),
    });
};

const purge = (riset) => {
    confirm.require({
        message: `Hapus permanen riset "${riset.title}"? Cover dan dokumen yang sudah diunggah akan ikut terhapus dan tidak bisa dipulihkan lagi.`,
        header: 'Hapus Permanen',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus Permanen',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.risets.force-destroy', riset.id)),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">
                    {{ isTrashed ? 'Sampah Riset' : 'Daftar Riset' }}
                </h1>
                <p class="text-sm text-surface-500">
                    {{ isTrashed
                        ? 'Riset yang sudah dihapus. Hapus permanen akan menghapus berkasnya juga.'
                        : 'Riset yang sudah masuk, termasuk arsip lama.' }}
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
                <InputText v-model="search" placeholder="Cari judul atau peneliti" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataView :value="risets.data" layout="list" data-key="id">
                <template #list="{ items }">
                    <div
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="flex flex-col sm:flex-row gap-4 py-5 sm:items-center"
                        :class="{ 'border-t border-surface-200': index > 0 }"
                    >
                        <div class="relative w-full sm:w-32 h-40 sm:h-24 shrink-0 rounded-lg overflow-hidden bg-surface-100">
                            <span class="absolute top-2 left-2 z-10">
                                <Tag
                                    :value="statusLabel(item.status)"
                                    :severity="statusSeverity[item.status] ?? 'secondary'"
                                    class="uppercase text-xs"
                                />
                            </span>
                            <img
                                :src="item.cover || '/images/placeholder-cover.svg'"
                                :alt="item.title"
                                class="w-full h-full object-cover"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-surface-500">{{ item.category ?? '—' }}</div>
                            <div class="text-lg font-semibold text-surface-900 truncate">{{ item.title }}</div>
                            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-surface-500">
                                <span>{{ item.peneliti ?? '—' }}</span>
                                <span>&middot;</span>
                                <span>{{ originLabel(item.origin) }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 bg-surface-100 rounded-full px-2.5 py-1 mt-2 text-xs text-surface-600">
                                <i class="pi pi-eye" />
                                <span>{{ item.views_count }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:flex-col sm:items-end gap-2 sm:gap-3 shrink-0">
                            <span class="text-xl font-bold text-surface-900">{{ item.tahun }}</span>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="!isTrashed"
                                    label="Lihat"
                                    icon="pi pi-eye"
                                    size="small"
                                    @click="router.visit(route('admin.risets.show', item.id))"
                                />
                                <ActionsMenu v-if="menuItems(item).length" :items="menuItems(item)" />
                            </div>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <span class="text-sm text-surface-400">
                        {{ isTrashed ? 'Tidak ada riset di sampah.' : 'Belum ada riset.' }}
                    </span>
                </template>
            </DataView>

            <Paginator
                v-if="risets.total > 0"
                class="mt-4"
                :rows="risets.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="risets.total"
                :first="(risets.current_page - 1) * risets.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
