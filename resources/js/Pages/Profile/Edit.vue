<script setup>
import { usePage } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const user = page.props.auth.user;

const form = useAutoClearForm({
    name: user.name,
    phone: user.phone ?? '',
});

const submit = () => form.patch(route('profile.update'));
</script>

<template>
    <div class="max-w-lg bg-white rounded-xl border border-surface-200 p-6">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-sm font-medium text-surface-700">Nama</label>
                <InputText id="name" v-model="form.name" class="w-full" required />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-sm font-medium text-surface-700">Nomor Telepon</label>
                <InputText id="phone" v-model="form.phone" class="w-full" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-surface-700">Email</label>
                <InputText :model-value="user.email" class="w-full" disabled />
            </div>

            <Button type="submit" label="Simpan" :loading="form.processing" class="self-start" />
        </form>
    </div>
</template>
