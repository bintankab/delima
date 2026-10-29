<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const page = usePage();

defineOptions({ layout: AuthLayout });

const form = useAutoClearForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold text-surface-900">Masuk</h1>

        <Message v-if="page.props.flash?.success" severity="success" :closable="false">
            {{ page.props.flash.success }}
        </Message>

        <Message v-if="form.errors.email" severity="error" :closable="false">
            {{ form.errors.email }}
        </Message>

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-surface-700">Email</label>
            <InputText id="email" v-model="form.email" type="email" autofocus required class="w-full" />
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium text-surface-700">Kata Sandi</label>
            <Password
                id="password"
                v-model="form.password"
                :feedback="false"
                toggleMask
                inputClass="w-full"
                class="w-full"
                required
            />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox v-model="form.remember" inputId="remember" binary />
            <label for="remember" class="text-sm text-surface-600">Ingat saya</label>
        </div>

        <Button type="submit" label="Masuk" class="w-full" :loading="form.processing" />

        <p class="text-sm text-surface-500 text-center">
            Belum punya akun?
            <Link :href="route('register')" class="text-indigo-600 font-medium">Daftar sebagai masyarakat</Link>
        </p>
    </form>
</template>
