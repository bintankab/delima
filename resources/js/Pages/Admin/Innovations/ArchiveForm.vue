<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    organisasis: { type: Array, required: true },
    categories: { type: Array, required: true },
    maxYear: { type: Number, required: true },
});

const jenisOptions = [
    { id: 'produk', name: 'Produk' },
    { id: 'proses', name: 'Proses' },
    { id: 'pelayanan', name: 'Pelayanan' },
];

const minDate = new Date(2021, 0, 1);
const maxDate = new Date();

const form = useForm({
    organisasi_id: null,
    category_id: null,
    title: '',
    jenis_inovasi: null,
    description: '',
    latar_belakang: '',
    tahun: null,
    published_at: null,
    cover: null,
    documents: [],
});

const formatDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return value;
    }

    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${value.getFullYear()}-${month}-${day}`;
};

const onCoverSelect = (event) => {
    form.cover = event.files?.[0] ?? null;
};

const clearCover = () => {
    form.cover = null;
};

const onDocumentsSelect = (event) => {
    form.documents = event.files ?? [];
};

const documentErrors = computed(() => Object.entries(form.errors)
    .filter(([key]) => key === 'documents' || key.startsWith('documents.'))
    .map(([, message]) => message));

const submit = () => {
    form.transform((data) => ({
        ...data,
        published_at: formatDate(data.published_at),
    })).post(route('admin.innovations.archive.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="max-w-3xl bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">Input Arsip Inovasi Lama</h1>
            <p class="text-sm text-surface-500 mt-1">
                Data inovasi sejak 2021 yang didigitalisasi. Langsung tayang, tanpa alur approval.
            </p>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="organisasi_id" class="text-sm font-medium text-surface-700">OPD asal</label>
                    <Select
                        input-id="organisasi_id"
                        v-model="form.organisasi_id"
                        :options="organisasis"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih OPD"
                        filter
                        class="w-full"
                    />
                    <small v-if="form.errors.organisasi_id" class="text-rose-600">{{ form.errors.organisasi_id }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="category_id" class="text-sm font-medium text-surface-700">Kategori</label>
                    <Select
                        input-id="category_id"
                        v-model="form.category_id"
                        :options="categories"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih kategori"
                        filter
                        class="w-full"
                    />
                    <small v-if="form.errors.category_id" class="text-rose-600">{{ form.errors.category_id }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="title" class="text-sm font-medium text-surface-700">Judul</label>
                <InputText id="title" v-model="form.title" class="w-full" autofocus />
                <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="jenis_inovasi" class="text-sm font-medium text-surface-700">Jenis Inovasi</label>
                    <Select
                        input-id="jenis_inovasi"
                        v-model="form.jenis_inovasi"
                        :options="jenisOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih jenis"
                        class="w-full"
                    />
                    <small v-if="form.errors.jenis_inovasi" class="text-rose-600">{{ form.errors.jenis_inovasi }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="tahun" class="text-sm font-medium text-surface-700">Tahun</label>
                    <InputNumber
                        input-id="tahun"
                        v-model="form.tahun"
                        :min="2021"
                        :max="maxYear"
                        :use-grouping="false"
                        class="w-full"
                    />
                    <small v-if="form.errors.tahun" class="text-rose-600">{{ form.errors.tahun }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="published_at" class="text-sm font-medium text-surface-700">Tanggal terbit</label>
                <DatePicker
                    input-id="published_at"
                    v-model="form.published_at"
                    date-format="yy-mm-dd"
                    :min-date="minDate"
                    :max-date="maxDate"
                    show-icon
                    class="w-full"
                />
                <small class="text-surface-400">Isi sesuai tanggal tayang arsip aslinya.</small>
                <small v-if="form.errors.published_at" class="text-rose-600">{{ form.errors.published_at }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="text-sm font-medium text-surface-700">Deskripsi</label>
                <Textarea id="description" v-model="form.description" rows="4" class="w-full" auto-resize />
                <small v-if="form.errors.description" class="text-rose-600">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="latar_belakang" class="text-sm font-medium text-surface-700">Latar belakang</label>
                <Textarea id="latar_belakang" v-model="form.latar_belakang" rows="3" class="w-full" auto-resize />
                <small v-if="form.errors.latar_belakang" class="text-rose-600">{{ form.errors.latar_belakang }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Cover</span>
                <FileUpload
                    mode="basic"
                    accept="image/jpeg,image/png,image/webp"
                    :max-file-size="10485760"
                    custom-upload
                    choose-label="Pilih gambar"
                    @select="onCoverSelect"
                    @clear="clearCover"
                />
                <div v-if="form.cover" class="flex items-center gap-2 text-sm text-surface-600">
                    <span>{{ form.cover.name }}</span>
                    <Button type="button" label="Hapus" severity="secondary" text size="small" @click="clearCover" />
                </div>
                <small v-if="form.errors.cover" class="text-rose-600">{{ form.errors.cover }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Dokumen pendukung</span>
                <FileUpload
                    accept=".pdf,.doc,.docx,application/pdf"
                    :multiple="true"
                    :max-file-size="10485760"
                    custom-upload
                    :show-upload-button="false"
                    choose-label="Pilih dokumen"
                    @select="onDocumentsSelect"
                    @remove="onDocumentsSelect"
                    @clear="onDocumentsSelect({ files: [] })"
                >
                    <template #empty>
                        <span class="text-sm text-surface-400">Seret dokumen PDF, DOC, atau DOCX ke sini.</span>
                    </template>
                </FileUpload>
                <small v-for="(message, index) in documentErrors" :key="index" class="text-rose-600">{{ message }}</small>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.innovations.index'))"
                />
            </div>
        </form>
    </div>
</template>
