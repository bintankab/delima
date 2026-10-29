<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
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
    innovations: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

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
    router.get(route('admin.innovations.mine'), {
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
    router.get(route('admin.innovations.mine'), {
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

const menuItems = (item) => {
    if (!item.editable) {
        return [];
    }

    return [
        { label: 'Ubah', icon: 'pi pi-pencil', command: () => router.visit(route('admin.innovations.mine.edit', item.id)) },
        { label: 'Hapus', icon: 'pi pi-trash', command: () => remove(item) },
    ];
};

const remove = (item) => {
    confirm.require({
        message: `Hapus inovasi "${item.title}"?`,
        header: 'Hapus Inovasi',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.innovations.mine.destroy', item.id), {
            data: { redirect_route: 'admin.innovations.mine' },
        }),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-surface-900">Inovasi Saya</h1>
            <p class="text-sm text-surface-500">Daftar inovasi yang pernah Anda ajukan beserta status reviewnya.</p>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari judul inovasi" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataView :value="innovations.data" layout="list" data-key="id">
                <template #list="{ items }">
                    <div
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="flex flex-col sm:flex-row gap-4 py-5 sm:items-center"
                        :class="{ 'border-t border-surface-200': index > 0 }"
                    >
                        <div class="relative w-full sm:w-32 h-40 sm:h-24 shrink-0 rounded-lg overflow-hidden bg-surface-100">
                            <img
                                :src="item.cover || '/images/placeholder-cover.svg'"
                                :alt="item.title"
                                class="w-full h-full object-cover"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-surface-500">{{ item.category ?? '—' }}</span>
                                <Tag
                                    :value="item.status_label"
                                    :severity="statusSeverity[item.status] ?? 'secondary'"
                                    class="text-xs"
                                />
                            </div>
                            <div class="text-lg font-semibold text-surface-900 truncate">{{ item.title }}</div>
                            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-surface-500">
                                <span>{{ item.jenis_inovasi_label ?? '—' }}</span>
                                <span>&middot;</span>
                                <span>{{ item.tahapan_label }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:flex-col sm:items-end gap-2 sm:gap-3 shrink-0">
                            <span class="text-xl font-bold text-surface-900">{{ item.tahun }}</span>
                            <div class="flex items-center gap-2">
                                <Button
                                    label="Lihat"
                                    icon="pi pi-eye"
                                    size="small"
                                    @click="router.visit(route('admin.innovations.mine.show', item.id))"
                                />
                                <ActionsMenu v-if="menuItems(item).length" :items="menuItems(item)" />
                            </div>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <span class="text-sm text-surface-400">Anda belum pernah mengajukan inovasi.</span>
                </template>
            </DataView>

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
