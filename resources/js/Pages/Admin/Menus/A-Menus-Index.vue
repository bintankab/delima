<script setup>
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import ActionsMenu from '@/Components/ActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    menu: { type: Object, required: true },
    items: { type: Array, required: true },
});

const confirm = useConfirm();

const move = (item, direction) => {
    router.post(route(`admin.menus.move-${direction}`, item.id), {}, { preserveScroll: true });
};

const remove = (item) => {
    confirm.require({
        message: item.children.length
            ? `Hapus "${item.label}"? Submenunya akan naik ke menu utama.`
            : `Hapus "${item.label}" dari menu?`,
        header: 'Hapus Menu',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.menus.destroy', item.id)),
    });
};

const menuItems = (item) => [
    {
        label: 'Ubah',
        icon: 'pi pi-pencil',
        command: () => router.visit(route('admin.menus.edit', item.id)),
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
                <h1 class="text-xl font-semibold text-white">{{ menu.name }}</h1>
                <p class="text-sm text-white/70">Atur tautan yang tampil di menu atas halaman publik.</p>
            </div>
            <Button
                label="Tambah Menu"
                icon="pi pi-plus"
                @click="router.visit(route('admin.menus.create'))"
            />
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <p v-if="!items.length" class="text-sm text-surface-400">Belum ada tautan menu.</p>

            <ul v-else class="flex flex-col">
                <li v-for="item in items" :key="item.id" class="border-b border-surface-100 last:border-b-0">
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="font-medium text-surface-900">{{ item.label }}</p>
                            <p class="text-sm text-surface-500 truncate">{{ item.url }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <Button
                                icon="pi pi-arrow-up"
                                text
                                rounded
                                aria-label="Naikkan"
                                :disabled="!item.can_move_up"
                                @click="move(item, 'up')"
                            />
                            <Button
                                icon="pi pi-arrow-down"
                                text
                                rounded
                                aria-label="Turunkan"
                                :disabled="!item.can_move_down"
                                @click="move(item, 'down')"
                            />
                            <ActionsMenu :items="menuItems(item)" />
                        </div>
                    </div>

                    <ul v-if="item.children.length" class="pl-6">
                        <li
                            v-for="child in item.children"
                            :key="child.id"
                            class="flex items-center justify-between gap-3 border-t border-dashed border-surface-100 py-3"
                        >
                            <div class="min-w-0">
                                <p class="font-medium text-surface-900">{{ child.label }}</p>
                                <p class="text-sm text-surface-500 truncate">{{ child.url }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <Button
                                    icon="pi pi-arrow-up"
                                    text
                                    rounded
                                    aria-label="Naikkan"
                                    :disabled="!child.can_move_up"
                                    @click="move(child, 'up')"
                                />
                                <Button
                                    icon="pi pi-arrow-down"
                                    text
                                    rounded
                                    aria-label="Turunkan"
                                    :disabled="!child.can_move_down"
                                    @click="move(child, 'down')"
                                />
                                <ActionsMenu :items="menuItems(child)" />
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</template>
