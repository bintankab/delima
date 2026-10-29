<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';

defineOptions({ layout: AuthLayout });

const page = usePage();

const form = useAutoClearForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold text-surface-900">Lupa Kata Sandi</h1>
        <p class="text-sm text-surface-500">
            Masukkan email Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
        </p>

        <Message v-if="page.props.flash?.success" severity="success" :closable="false">
            {{ page.props.flash.success }}
        </Message>

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-surface-700">Email</label>
            <InputText id="email" v-model="form.email" type="email" autofocus required class="w-full" />
            <small v-if="form.errors.email" class="text-rose-600">{{ form.errors.email }}</small>
        </div>

        <Button type="submit" label="Kirim Tautan Reset" class="w-full" :loading="form.processing" />

        <p class="text-sm text-surface-500 text-center">
            <Link :href="route('login')" class="text-indigo-600 font-medium">Kembali ke halaman masuk</Link>
        </p>
    </form>
</template>
