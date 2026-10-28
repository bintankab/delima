<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    organisasis: { type: Array, required: true },
    categories: { type: Array, required: true },
    jenisOptions: { type: Array, required: true },
    tahapanOptions: { type: Array, required: true },
    maxYear: { type: Number, required: true },
});

const minDate = new Date(2021, 0, 1);
const maxDate = new Date();

const form = useForm({
    organisasi_id: null,
    category_id: null,
    title: '',
    jenis_inovasi: null,
    tahapan: null,
    description: '',
    latar_belakang: '',
    manfaat: '',
    link_video: '',
    tahun: null,
    testing_at: null,
    implementation_at: null,
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

const formatFileSize = (bytes) => {
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
};

const documentErrors = computed(() => Object.entries(form.errors)
    .filter(([key]) => key === 'documents' || key.startsWith('documents.'))
    .map(([, message]) => message));

const submit = () => {
    form.transform((data) => ({
        ...data,
        testing_at: formatDate(data.testing_at),
        implementation_at: formatDate(data.implementation_at),
        link_video: data.link_video || null,
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
                <label for="tahapan" class="text-sm font-medium text-surface-700">Tahapan Inovasi</label>
                <Select
                    input-id="tahapan"
                    v-model="form.tahapan"
                    :options="tahapanOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="- Pilih Tahapan Inovasi -"
                    class="w-full"
                />
                <small v-if="form.errors.tahapan" class="text-rose-600">{{ form.errors.tahapan }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="testing_at" class="text-sm font-medium text-surface-700">Tanggal Uji Coba</label>
                    <DatePicker
                        input-id="testing_at"
                        v-model="form.testing_at"
                        date-format="yy-mm-dd"
                        :min-date="minDate"
                        :max-date="maxDate"
                        show-icon
                        show-button-bar
                        class="w-full"
                    />
                    <small v-if="form.errors.testing_at" class="text-rose-600">{{ form.errors.testing_at }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="implementation_at" class="text-sm font-medium text-surface-700">Tanggal Implementasi</label>
                    <DatePicker
                        input-id="implementation_at"
                        v-model="form.implementation_at"
                        date-format="yy-mm-dd"
                        :min-date="minDate"
                        :max-date="maxDate"
                        show-icon
                        class="w-full"
                    />
                    <small class="text-surface-400">Isi sesuai tanggal tayang arsip aslinya.</small>
                    <small v-if="form.errors.implementation_at" class="text-rose-600">{{ form.errors.implementation_at }}</small>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="text-sm font-medium text-surface-700">Deskripsi</label>
                <RichTextEditor v-model="form.description" placeholder="Tulis deskripsi inovasi di sini..." height="220px" />
                <small v-if="form.errors.description" class="text-rose-600">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="latar_belakang" class="text-sm font-medium text-surface-700">Latar belakang</label>
                <RichTextEditor v-model="form.latar_belakang" placeholder="Tulis latar belakang di sini..." height="160px" />
                <small v-if="form.errors.latar_belakang" class="text-rose-600">{{ form.errors.latar_belakang }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="manfaat" class="text-sm font-medium text-surface-700">Manfaat</label>
                <RichTextEditor v-model="form.manfaat" placeholder="Tulis manfaat inovasi di sini..." height="160px" />
                <small v-if="form.errors.manfaat" class="text-rose-600">{{ form.errors.manfaat }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="link_video" class="text-sm font-medium text-surface-700">Link Video</label>
                <InputText
                    id="link_video"
                    v-model="form.link_video"
                    placeholder="https://youtube.com/watch?v=..."
                    class="w-full"
                />
                <small class="text-surface-400">Tautan video eksternal (YouTube, Vimeo, Google Drive, atau sumber lain).</small>
                <small v-if="form.errors.link_video" class="text-rose-600">{{ form.errors.link_video }}</small>
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
                    <template #content="{ files, removeFileCallback }">
                        <ul v-if="files.length" class="flex flex-col gap-2 mt-2">
                            <li
                                v-for="(file, index) in files"
                                :key="file.name + file.size"
                                class="flex items-center justify-between gap-2 text-sm bg-surface-50 rounded-lg px-3 py-2"
                            >
                                <span class="flex items-center gap-2 text-surface-700 min-w-0">
                                    <i class="pi pi-file" />
                                    <span class="truncate">{{ file.name }}</span>
                                    <span class="text-surface-400 shrink-0">({{ formatFileSize(file.size) }})</span>
                                </span>
                                <Button
                                    type="button"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    aria-label="Hapus dokumen"
                                    @click="removeFileCallback(index)"
                                />
                            </li>
                        </ul>
                    </template>
                    <template #empty>
                        <span class="text-sm text-surface-400">Seret dokumen PDF, DOC, atau DOCX ke sini.</span>
                    </template>
                </FileUpload>
                <small v-for="(message, index) in documentErrors" :key="index" class="text-rose-600">{{ message }}</small>
            </div>

            <div v-if="form.progress" class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Mengunggah berkas... {{ form.progress.percentage }}%</span>
                <ProgressBar :value="form.progress.percentage" :show-value="false" />
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
