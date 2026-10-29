<script setup>
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, required: true },
    organisasis: { type: Array, required: true },
    lockedRole: { type: String, default: null },
});

const form = useAutoClearForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    password: '',
    password_confirmation: '',
    is_active: props.user?.is_active ?? true,
    role: props.user?.role ?? props.lockedRole,
    organisasi_id: props.user?.organisasi_id ?? null,
});

const submit = () => {
    if (props.user) {
        form.put(route('admin.users.update', props.user.id));

        return;
    }

    form.post(route('admin.users.store'));
};
</script>

<template>
    <div class="max-w-lg bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ lockedRole ? (user ? 'Ubah Staf ASN' : 'Tambah Staf ASN') : (user ? 'Ubah Pengguna' : 'Tambah Pengguna') }}
            </h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-sm font-medium text-surface-700">Nama</label>
                <InputText id="name" v-model="form.name" class="w-full" autofocus />
                <small v-if="form.errors.name" class="text-rose-600">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-medium text-surface-700">Email</label>
                <InputText id="email" v-model="form.email" type="email" class="w-full" />
                <small v-if="form.errors.email" class="text-rose-600">{{ form.errors.email }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="phone" class="text-sm font-medium text-surface-700">Telepon</label>
                <InputText id="phone" v-model="form.phone" class="w-full" />
                <small v-if="form.errors.phone" class="text-rose-600">{{ form.errors.phone }}</small>
            </div>

            <div v-if="!lockedRole" class="flex flex-col gap-1.5">
                <label for="role" class="text-sm font-medium text-surface-700">Role</label>
                <Select
                    input-id="role"
                    v-model="form.role"
                    :options="roles"
                    option-label="name"
                    option-value="id"
                    placeholder="Pilih role"
                    class="w-full"
                />
                <small v-if="form.errors.role" class="text-rose-600">{{ form.errors.role }}</small>
            </div>

            <div v-if="!lockedRole && ['admin-opd', 'asn'].includes(form.role)" class="flex flex-col gap-1.5">
                <label for="organisasi_id" class="text-sm font-medium text-surface-700">Organisasi/OPD</label>
                <Select
                    input-id="organisasi_id"
                    v-model="form.organisasi_id"
                    :options="organisasis"
                    option-label="name"
                    option-value="id"
                    placeholder="Pilih organisasi"
                    filter
                    class="w-full"
                />
                <small v-if="form.errors.organisasi_id" class="text-rose-600">{{ form.errors.organisasi_id }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-medium text-surface-700">
                        Kata Sandi
                        <span v-if="user" class="text-surface-400 font-normal">(kosongkan jika tidak diubah)</span>
                    </label>
                    <Password
                        id="password"
                        v-model="form.password"
                        :feedback="false"
                        toggle-mask
                        input-class="w-full"
                        class="w-full"
                    />
                    <small v-if="form.errors.password" class="text-rose-600">{{ form.errors.password }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-sm font-medium text-surface-700">Konfirmasi Kata Sandi</label>
                    <Password
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :feedback="false"
                        toggle-mask
                        input-class="w-full"
                        class="w-full"
                    />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <ToggleSwitch input-id="is_active" v-model="form.is_active" />
                <label for="is_active" class="text-sm font-medium text-surface-700">Aktif</label>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.users.index'))"
                />
            </div>
        </form>
    </div>
</template>
