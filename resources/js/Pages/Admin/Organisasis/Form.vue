<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    organisasi: { type: Object, default: null },
});

const form = useForm({
    name: props.organisasi?.name ?? '',
    type: props.organisasi?.type ?? '',
    code: props.organisasi?.code ?? '',
    address: props.organisasi?.address ?? '',
    contact_person: props.organisasi?.contact_person ?? '',
    phone: props.organisasi?.phone ?? '',
    email: props.organisasi?.email ?? '',
    is_active: props.organisasi?.is_active ?? true,
    logo: null,
});

const newLogoPreview = ref(null);

const onLogoSelect = (event) => {
    const file = event.files?.[0] ?? null;
    form.logo = file;
    newLogoPreview.value = file ? URL.createObjectURL(file) : null;
};

const clearLogo = () => {
    form.logo = null;
    newLogoPreview.value = null;
};

const submit = () => {
    if (props.organisasi) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            route('admin.organisasis.update', props.organisasi.id),
            { forceFormData: true },
        );

        return;
    }

    form.post(route('admin.organisasis.store'), { forceFormData: true });
};
</script>

<template>
    <div class="max-w-2xl bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ organisasi ? 'Ubah Organisasi/OPD' : 'Tambah Organisasi/OPD' }}
            </h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-sm font-medium text-surface-700">Nama</label>
                    <InputText id="name" v-model="form.name" class="w-full" autofocus />
                    <small v-if="form.errors.name" class="text-rose-600">{{ form.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="type" class="text-sm font-medium text-surface-700">Jenis</label>
                    <InputText id="type" v-model="form.type" class="w-full" placeholder="Dinas, Badan, Kecamatan, ..." />
                    <small v-if="form.errors.type" class="text-rose-600">{{ form.errors.type }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="code" class="text-sm font-medium text-surface-700">Kode</label>
                <InputText id="code" v-model="form.code" class="w-full sm:max-w-xs" />
                <small v-if="form.errors.code" class="text-rose-600">{{ form.errors.code }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="address" class="text-sm font-medium text-surface-700">Alamat</label>
                <Textarea id="address" v-model="form.address" rows="3" class="w-full" auto-resize />
                <small v-if="form.errors.address" class="text-rose-600">{{ form.errors.address }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-1.5">
                    <label for="contact_person" class="text-sm font-medium text-surface-700">Kontak</label>
                    <InputText id="contact_person" v-model="form.contact_person" class="w-full" />
                    <small v-if="form.errors.contact_person" class="text-rose-600">{{ form.errors.contact_person }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="text-sm font-medium text-surface-700">Telepon</label>
                    <InputText id="phone" v-model="form.phone" class="w-full" />
                    <small v-if="form.errors.phone" class="text-rose-600">{{ form.errors.phone }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-medium text-surface-700">Email</label>
                    <InputText id="email" v-model="form.email" type="email" class="w-full" />
                    <small v-if="form.errors.email" class="text-rose-600">{{ form.errors.email }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Logo</span>
                <div v-if="organisasi?.logo && !newLogoPreview" class="flex items-center gap-3">
                    <img :src="organisasi.logo.thumb" alt="Logo saat ini" class="w-16 h-16 rounded-lg object-cover border border-surface-200" />
                    <span class="text-xs text-surface-500">Logo saat ini. Pilih gambar baru untuk menggantinya.</span>
                </div>
                <img v-if="newLogoPreview" :src="newLogoPreview" alt="Pratinjau logo baru" class="w-16 h-16 rounded-lg object-cover border border-surface-200" />
                <FileUpload
                    mode="basic"
                    accept="image/jpeg,image/png,image/webp"
                    :max-file-size="5242880"
                    custom-upload
                    choose-label="Ganti logo"
                    @select="onLogoSelect"
                    @clear="clearLogo"
                />
                <small v-if="form.errors.logo" class="text-rose-600">{{ form.errors.logo }}</small>
            </div>

            <div class="flex items-center gap-3">
                <ToggleSwitch input-id="is_active" v-model="form.is_active" />
                <label for="is_active" class="text-sm font-medium text-surface-700">Aktif</label>
                <small v-if="form.errors.is_active" class="text-rose-600">{{ form.errors.is_active }}</small>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.organisasis.index'))"
                />
            </div>
        </form>
    </div>
</template>
