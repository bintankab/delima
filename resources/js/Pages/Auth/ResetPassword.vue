<script setup>
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';

defineOptions({ layout: AuthLayout });

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useAutoClearForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h1 class="text-lg font-semibold text-surface-900">Atur Ulang Kata Sandi</h1>

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-surface-700">Email</label>
            <InputText id="email" v-model="form.email" type="email" required class="w-full" />
            <small v-if="form.errors.email" class="text-rose-600">{{ form.errors.email }}</small>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium text-surface-700">Kata Sandi Baru</label>
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
            <small v-if="form.errors.password_confirmation" class="text-rose-600">{{ form.errors.password_confirmation }}</small>
        </div>

        <Button type="submit" label="Atur Ulang Kata Sandi" class="w-full" :loading="form.processing" />
    </form>
</template>
