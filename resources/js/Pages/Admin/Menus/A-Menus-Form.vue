<script setup>
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    item: { type: Object, default: null },
    parents: { type: Array, required: true },
});

const form = useAutoClearForm({
    label: props.item?.label ?? '',
    url: props.item?.url ?? '',
    parent_id: props.item?.parent_id ?? null,
    opens_in_new_tab: props.item?.opens_in_new_tab ?? false,
});

const submit = () => {
    if (props.item) {
        form.put(route('admin.menus.update', props.item.id));

        return;
    }

    form.post(route('admin.menus.store'));
};
</script>

<template>
    <div class="max-w-lg bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ item ? 'Ubah Menu' : 'Tambah Menu' }}
            </h1>
            <p class="text-sm text-surface-500 mt-1">Tautan ini tampil di menu atas halaman publik.</p>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="label" class="text-sm font-medium text-surface-700">Label</label>
                <InputText id="label" v-model="form.label" class="w-full" autofocus />
                <small v-if="form.errors.label" class="text-rose-600">{{ form.errors.label }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="url" class="text-sm font-medium text-surface-700">Alamat</label>
                <InputText id="url" v-model="form.url" class="w-full" placeholder="/riset" />
                <small class="text-surface-400">Halaman internal diawali garis miring. Situs lain memakai http atau https.</small>
                <small v-if="form.errors.url" class="text-rose-600">{{ form.errors.url }}</small>
            </div>

            <div v-if="!item?.has_children" class="flex flex-col gap-1.5">
                <label for="parent_id" class="text-sm font-medium text-surface-700">Induk</label>
                <Select
                    input-id="parent_id"
                    v-model="form.parent_id"
                    :options="parents"
                    option-label="label"
                    option-value="id"
                    placeholder="Menu utama"
                    show-clear
                    class="w-full"
                />
                <small class="text-surface-400">Kosongkan untuk menaruh tautan di menu utama. Submenu hanya satu tingkat.</small>
                <small v-if="form.errors.parent_id" class="text-rose-600">{{ form.errors.parent_id }}</small>
            </div>
            <p v-else class="text-sm text-surface-500">Item ini punya submenu, jadi tidak bisa dijadikan submenu.</p>

            <div class="flex items-center gap-2">
                <Checkbox v-model="form.opens_in_new_tab" input-id="opens_in_new_tab" binary />
                <label for="opens_in_new_tab" class="text-sm text-surface-700">Buka di tab baru</label>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.menus.index'))"
                />
            </div>
        </form>
    </div>
</template>
