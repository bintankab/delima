<script setup>
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    category: { type: Object, default: null },
    parents: { type: Array, required: true },
});

const form = useAutoClearForm({
    name: props.category?.name ?? '',
    parent_id: props.category?.parent_id ?? null,
    description: props.category?.description ?? '',
});

const submit = () => {
    if (props.category) {
        form.put(route('admin.post-categories.update', props.category.id));

        return;
    }

    form.post(route('admin.post-categories.store'));
};
</script>

<template>
    <div class="max-w-lg bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ category ? 'Ubah Kategori' : 'Tambah Kategori' }}
            </h1>
            <p v-if="category" class="text-sm text-surface-500 mt-1">Slug: {{ category.slug }}</p>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-sm font-medium text-surface-700">Nama</label>
                <InputText id="name" v-model="form.name" class="w-full" autofocus />
                <small v-if="form.errors.name" class="text-rose-600">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="parent_id" class="text-sm font-medium text-surface-700">Induk</label>
                <Select
                    input-id="parent_id"
                    v-model="form.parent_id"
                    :options="parents"
                    option-label="name"
                    option-value="id"
                    placeholder="Tanpa induk"
                    show-clear
                    filter
                    class="w-full"
                />
                <small v-if="form.errors.parent_id" class="text-rose-600">{{ form.errors.parent_id }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="text-sm font-medium text-surface-700">Deskripsi</label>
                <Textarea id="description" v-model="form.description" rows="3" class="w-full" auto-resize />
                <small v-if="form.errors.description" class="text-rose-600">{{ form.errors.description }}</small>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.post-categories.index'))"
                />
            </div>
        </form>
    </div>
</template>
