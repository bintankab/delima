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
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');

const roleLabel = (role) => ({
    'super-admin': 'Super Admin',
    'admin-opd': 'Admin OPD',
    reviewer: 'Reviewer',
}[role] ?? role ?? '—');

const applySearch = () => {
    router.get(route('admin.users.index'), {
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
    router.get(route('admin.users.index'), {
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
    router.get(route('admin.users.index'), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        sort: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
    }, {
        preserveState: true,
        replace: true,
    });
};

const remove = (user) => {
    confirm.require({
        message: `Hapus pengguna "${user.name}"?`,
        header: 'Hapus Pengguna',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.users.destroy', user.id)),
    });
};

const menuItems = (user) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.users.edit', user.id)),
    },
    {
        label: 'Hapus',
        icon: 'pi pi-trash',
        disabled: user.is_self,
        command: () => remove(user),
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-surface-900">Pengguna</h1>
                <p class="text-sm text-surface-500">Akun pengguna yang dapat mengakses panel admin.</p>
            </div>
            <Button
                label="Tambah Pengguna"
                icon="pi pi-plus"
                @click="router.visit(route('admin.users.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <form class="flex gap-2 mb-4" @submit.prevent="applySearch">
                <InputText v-model="search" placeholder="Cari nama atau email" class="w-full sm:max-w-xs" />
                <Button type="submit" label="Cari" severity="secondary" />
            </form>

            <DataTable
                :value="users.data"
                size="small"
                lazy
                :sort-field="filters.sort"
                :sort-order="filters.direction === 'desc' ? -1 : 1"
                @sort="onSort"
            >
                <Column field="name" header="Nama" sortable />
                <Column field="email" header="Email" sortable />
                <Column field="phone" header="Telepon" sortable>
                    <template #body="{ data }">{{ data.phone ?? '—' }}</template>
                </Column>
                <Column header="Role">
                    <template #body="{ data }">
                        <Tag :value="roleLabel(data.role)" severity="info" />
                    </template>
                </Column>
                <Column header="Organisasi/OPD">
                    <template #body="{ data }">{{ data.organisasi ?? '—' }}</template>
                </Column>
                <Column field="is_active" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                        />
                    </template>
                </Column>
                <Column header="Aksi" style="width: 5rem">
                    <template #body="{ data }">
                        <ActionsMenu :items="menuItems(data)" />
                    </template>
                </Column>
                <template #empty>
                    <span class="text-sm text-surface-400">Belum ada pengguna.</span>
                </template>
            </DataTable>

            <Paginator
                v-if="users.total > 0"
                class="mt-4"
                :rows="users.per_page"
                :rows-per-page-options="[5, 10, 20, 50, 100]"
                :total-records="users.total"
                :first="(users.current_page - 1) * users.per_page"
                @page="onPage"
            />
        </div>
    </div>
</template>
