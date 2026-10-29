<script setup>
import { Link } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout });

const form = useAutoClearForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold text-surface-900">Daftar sebagai Masyarakat</h1>
        <p class="text-sm text-surface-500">
            Setelah mendaftar, Anda perlu memverifikasi email sebelum dapat masuk.
        </p>

        <div class="flex flex-col gap-1.5">
            <label for="name" class="text-sm font-medium text-surface-700">Nama</label>
            <InputText id="name" v-model="form.name" autofocus required class="w-full" />
            <small v-if="form.errors.name" class="text-rose-600">{{ form.errors.name }}</small>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-surface-700">Email</label>
            <InputText id="email" v-model="form.email" type="email" required class="w-full" />
            <small v-if="form.errors.email" class="text-rose-600">{{ form.errors.email }}</small>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium text-surface-700">Kata Sandi</label>
            <Password
                id="password"
                v-model="form.password"
                toggleMask
                inputClass="w-full"
                class="w-full"
                required
            />
            <small v-if="form.errors.password" class="text-rose-600">{{ form.errors.password }}</small>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password_confirmation" class="text-sm font-medium text-surface-700">Konfirmasi Kata Sandi</label>
            <Password
                id="password_confirmation"
                v-model="form.password_confirmation"
                :feedback="false"
                toggleMask
                inputClass="w-full"
                class="w-full"
                required
            />
        </div>

        <Button type="submit" label="Daftar" class="w-full" :loading="form.processing" />

        <p class="text-sm text-surface-500 text-center">
            Sudah punya akun?
            <Link :href="route('login')" class="text-indigo-600 font-medium">Masuk</Link>
        </p>
    </form>
</template>
