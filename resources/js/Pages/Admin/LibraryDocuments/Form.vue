<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    document: { type: Object, default: null },
    types: { type: Array, required: true },
    organisasis: { type: Array, required: true },
});

const toDate = (value) => (value ? new Date(`${value}T00:00:00`) : null);

const formatDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return value;
    }

    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${value.getFullYear()}-${month}-${day}`;
};

const form = useForm({
    title: props.document?.title ?? '',
    nomor_dokumen: props.document?.nomor_dokumen ?? '',
    penyusun: props.document?.penyusun ?? '',
    tahun_terbit: props.document?.tahun_terbit ?? null,
    tanggal_terbit: toDate(props.document?.tanggal_terbit),
    description: props.document?.description ?? '',
    type: props.document?.type ?? null,
    organisasi_id: props.document?.organisasi_id ?? null,
    is_public: props.document?.is_public ?? true,
    file: null,
});

const newFileName = ref(null);

const onFileSelect = (event) => {
    const file = event.files?.[0] ?? null;
    form.file = file;
    newFileName.value = file?.name ?? null;
};

const clearFile = () => {
    form.file = null;
    newFileName.value = null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        tanggal_terbit: formatDate(data.tanggal_terbit),
        _method: props.document ? 'put' : 'post',
    })).post(
        props.document
            ? route('admin.library-documents.update', props.document.id)
            : route('admin.library-documents.store'),
        { forceFormData: true },
    );
};
</script>

<template>
    <div class="max-w-2xl bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ document ? 'Ubah Dokumen' : 'Tambah Dokumen' }}
            </h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="title" class="text-sm font-medium text-surface-700">Judul</label>
                <InputText id="title" v-model="form.title" class="w-full" autofocus />
                <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="type" class="text-sm font-medium text-surface-700">Jenis</label>
                    <Select
                        input-id="type"
                        v-model="form.type"
                        :options="types"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih jenis"
                        class="w-full"
                    />
                    <small v-if="form.errors.type" class="text-rose-600">{{ form.errors.type }}</small>
                </div>

                <div v-if="organisasis.length" class="flex flex-col gap-1.5">
                    <label for="organisasi_id" class="text-sm font-medium text-surface-700">Organisasi/OPD</label>
                    <Select
                        input-id="organisasi_id"
                        v-model="form.organisasi_id"
                        :options="organisasis"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih organisasi"
                        show-clear
                        filter
                        class="w-full"
                    />
                    <small v-if="form.errors.organisasi_id" class="text-rose-600">{{ form.errors.organisasi_id }}</small>
                </div>
                <div v-else-if="document?.organisasi" class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-surface-700">Organisasi/OPD</label>
                    <p class="text-sm text-surface-600">{{ document.organisasi }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="nomor_dokumen" class="text-sm font-medium text-surface-700">Nomor Dokumen</label>
                    <InputText id="nomor_dokumen" v-model="form.nomor_dokumen" class="w-full" />
                    <small v-if="form.errors.nomor_dokumen" class="text-rose-600">{{ form.errors.nomor_dokumen }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="penyusun" class="text-sm font-medium text-surface-700">Penyusun</label>
                    <InputText id="penyusun" v-model="form.penyusun" class="w-full" />
                    <small v-if="form.errors.penyusun" class="text-rose-600">{{ form.errors.penyusun }}</small>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="tahun_terbit" class="text-sm font-medium text-surface-700">Tahun Terbit</label>
                    <InputNumber
                        input-id="tahun_terbit"
                        v-model="form.tahun_terbit"
                        :use-grouping="false"
                        class="w-full"
                    />
                    <small v-if="form.errors.tahun_terbit" class="text-rose-600">{{ form.errors.tahun_terbit }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="tanggal_terbit" class="text-sm font-medium text-surface-700">Tanggal Terbit</label>
                    <DatePicker
                        input-id="tanggal_terbit"
                        v-model="form.tanggal_terbit"
                        date-format="yy-mm-dd"
                        show-icon
                        class="w-full"
                    />
                    <small v-if="form.errors.tanggal_terbit" class="text-rose-600">{{ form.errors.tanggal_terbit }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="text-sm font-medium text-surface-700">Deskripsi</label>
                <Textarea id="description" v-model="form.description" rows="3" class="w-full" auto-resize />
                <small v-if="form.errors.description" class="text-rose-600">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Berkas</span>
                <div v-if="document?.file && !newFileName" class="flex items-center gap-3 text-sm bg-surface-50 rounded-lg px-3 py-2">
                    <i class="pi pi-file text-surface-500" />
                    <span class="truncate">{{ document.file.name }}</span>
                    <span class="text-surface-400 shrink-0">({{ document.file.size }})</span>
                    <a :href="document.file.url" target="_blank" rel="noopener" class="text-indigo-600 ml-auto shrink-0">Unduh</a>
                </div>
                <p v-if="newFileName" class="text-sm text-surface-600">Berkas baru: {{ newFileName }}</p>
                <FileUpload
                    mode="basic"
                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                    :max-file-size="20971520"
                    custom-upload
                    :choose-label="document ? 'Ganti berkas' : 'Pilih berkas'"
                    @select="onFileSelect"
                    @clear="clearFile"
                />
                <small v-if="form.errors.file" class="text-rose-600">{{ form.errors.file }}</small>
            </div>

            <div class="flex items-center gap-3">
                <ToggleSwitch input-id="is_public" v-model="form.is_public" />
                <label for="is_public" class="text-sm font-medium text-surface-700">Publik</label>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.library-documents.index'))"
                />
            </div>
        </form>
    </div>
</template>
