<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import { useConfirm } from 'primevue/useconfirm';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

defineOptions({ layout: AdminLayout });

const confirm = useConfirm();

const props = defineProps({
    riset: { type: Object, required: true },
    organisasis: { type: Array, required: true },
    categories: { type: Array, required: true },
    maxYear: { type: Number, required: true },
});

const minDate = new Date(2021, 0, 1);
const maxDate = new Date();

const toDate = (value) => (value ? new Date(`${value}T00:00:00`) : null);

const form = useAutoClearForm({
    organisasi_id: props.riset.organisasi_id,
    category_id: props.riset.category_id,
    title: props.riset.title,
    peneliti: props.riset.peneliti,
    abstrak: props.riset.abstrak,
    latar_belakang: props.riset.latar_belakang ?? '',
    metodologi: props.riset.metodologi ?? '',
    tahun: props.riset.tahun,
    published_at: toDate(props.riset.published_at),
    cover: null,
    documents: [],
    remove_document_ids: [],
});

const existingDocuments = ref([...props.riset.documents]);
const newCoverPreview = ref(null);

const formatDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return value;
    }

    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${value.getFullYear()}-${month}-${day}`;
};

const onCoverSelect = (event) => {
    const file = event.files?.[0] ?? null;
    form.cover = file;
    newCoverPreview.value = file ? URL.createObjectURL(file) : null;
};

const clearCover = () => {
    form.cover = null;
    newCoverPreview.value = null;
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

const removeExistingDocument = (document) => {
    confirm.require({
        message: `Hapus dokumen "${document.name}"?`,
        header: 'Hapus Dokumen',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Tidak',
        acceptLabel: 'Ya',
        acceptClass: 'p-button-danger',
        accept: () => {
            existingDocuments.value = existingDocuments.value.filter((item) => item.id !== document.id);
            form.remove_document_ids.push(document.id);
        },
    });
};

const documentErrors = computed(() => Object.entries(form.errors)
    .filter(([key]) => key === 'documents' || key.startsWith('documents.'))
    .map(([, message]) => message));

const submit = () => {
    form.transform((data) => ({
        ...data,
        published_at: formatDate(data.published_at),
        _method: 'put',
    })).post(route('admin.risets.update', props.riset.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="max-w-3xl bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">Ubah Riset</h1>
            <p class="text-sm text-surface-500 mt-1">Slug: {{ riset.slug }}</p>
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
                    <label for="peneliti" class="text-sm font-medium text-surface-700">Peneliti</label>
                    <InputText id="peneliti" v-model="form.peneliti" class="w-full" />
                    <small v-if="form.errors.peneliti" class="text-rose-600">{{ form.errors.peneliti }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="tahun" class="text-sm font-medium text-surface-700">Tahun</label>
                    <InputNumber
                        input-id="tahun"
                        v-model="form.tahun"
                        :min="2021"
                        :max="maxYear"
                        :use-grouping="false"
                        :pt="{ pcInputText: { root: { maxlength: 4, inputmode: 'numeric' } } }"
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
                <small v-if="form.errors.published_at" class="text-rose-600">{{ form.errors.published_at }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="abstrak" class="text-sm font-medium text-surface-700">Abstrak</label>
                <RichTextEditor v-model="form.abstrak" placeholder="Tulis abstrak riset di sini..." height="220px" />
                <small v-if="form.errors.abstrak" class="text-rose-600">{{ form.errors.abstrak }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="latar_belakang" class="text-sm font-medium text-surface-700">Latar belakang</label>
                <RichTextEditor v-model="form.latar_belakang" placeholder="Tulis latar belakang di sini..." height="160px" />
                <small v-if="form.errors.latar_belakang" class="text-rose-600">{{ form.errors.latar_belakang }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="metodologi" class="text-sm font-medium text-surface-700">Metodologi</label>
                <RichTextEditor v-model="form.metodologi" placeholder="Tulis metodologi riset di sini..." height="160px" />
                <small v-if="form.errors.metodologi" class="text-rose-600">{{ form.errors.metodologi }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Cover</span>
                <div v-if="riset.cover && !newCoverPreview" class="flex items-center gap-3">
                    <img :src="riset.cover.thumb" alt="Cover saat ini" class="w-24 h-18 rounded-lg object-cover border border-surface-200" />
                    <span class="text-xs text-surface-500">Cover saat ini. Pilih gambar baru untuk menggantinya.</span>
                </div>
                <img v-if="newCoverPreview" :src="newCoverPreview" alt="Pratinjau cover baru" class="w-24 h-18 rounded-lg object-cover border border-surface-200" />
                <FileUpload
                    mode="basic"
                    accept="image/jpeg,image/png,image/webp"
                    :max-file-size="10485760"
                    custom-upload
                    choose-label="Ganti gambar"
                    @select="onCoverSelect"
                    @clear="clearCover"
                />
                <small v-if="form.errors.cover" class="text-rose-600">{{ form.errors.cover }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Dokumen laporan</span>

                <ul v-if="existingDocuments.length" class="flex flex-col gap-2 mb-1">
                    <li
                        v-for="document in existingDocuments"
                        :key="document.id"
                        class="flex items-center justify-between gap-2 text-sm bg-surface-50 rounded-lg px-3 py-2"
                    >
                        <span class="flex items-center gap-2 text-surface-700 min-w-0">
                            <i class="pi pi-file" />
                            <span class="truncate">{{ document.name }}</span>
                            <span class="text-surface-400 shrink-0">({{ document.size }})</span>
                        </span>
                        <Button
                            type="button"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            aria-label="Hapus dokumen"
                            @click="removeExistingDocument(document)"
                        />
                    </li>
                </ul>

                <FileUpload
                    accept=".pdf,.doc,.docx,application/pdf"
                    :multiple="true"
                    :max-file-size="10485760"
                    custom-upload
                    :show-upload-button="false"
                    choose-label="Tambah dokumen"
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
                    @click="router.visit(route('admin.risets.show', riset.id))"
                />
            </div>
        </form>
    </div>
</template>
