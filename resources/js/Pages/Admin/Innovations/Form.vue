<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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
const page = usePage();

const props = defineProps({
    innovation: { type: Object, default: null },
    categories: { type: Array, required: true },
    jenisOptions: { type: Array, required: true },
    tahapanOptions: { type: Array, required: true },
    inisiatorOptions: { type: Array, required: true },
    inisiator: { type: String, default: null },
    inisiatorLocked: { type: Boolean, default: false },
    maxYear: { type: Number, required: true },
    updateRoute: { type: String, default: 'admin.innovations.update' },
    showRoute: { type: String, default: 'admin.innovations.show' },
});

const isEditing = computed(() => props.innovation !== null);

const inisiatorLabel = computed(
    () => props.inisiatorOptions.find((option) => option.id === form.inisiator)?.name ?? '—',
);

const canAccessIndex = computed(
    () => ['super-admin', 'admin-opd'].some((role) => (page.props.auth?.roles ?? []).includes(role)),
);

const cancelHref = computed(() => {
    if (isEditing.value) {
        return route(props.showRoute, props.innovation.id);
    }

    return canAccessIndex.value ? route('admin.innovations.index') : route('home');
});

const minDate = new Date(2021, 0, 1);
const maxDate = new Date();

const toDate = (value) => (value ? new Date(`${value}T00:00:00`) : null);

const form = useAutoClearForm({
    inisiator: props.innovation?.inisiator ?? props.inisiator,
    category_id: props.innovation?.category_id ?? null,
    title: props.innovation?.title ?? '',
    jenis_inovasi: props.innovation?.jenis_inovasi ?? null,
    tahapan: props.innovation?.tahapan ?? null,
    description: props.innovation?.description ?? '',
    latar_belakang: props.innovation?.latar_belakang ?? '',
    manfaat: props.innovation?.manfaat ?? '',
    link_video: props.innovation?.link_video ?? '',
    link_file_pendukung: props.innovation?.link_file_pendukung ?? '',
    tahun: props.innovation?.tahun ?? null,
    testing_at: toDate(props.innovation?.testing_at),
    implementation_at: toDate(props.innovation?.implementation_at),
    cover: null,
    documents: [],
    remove_document_ids: [],
});

const existingDocuments = ref(props.innovation ? [...props.innovation.documents] : []);
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
    const transformed = form.transform((data) => ({
        ...data,
        testing_at: formatDate(data.testing_at),
        implementation_at: formatDate(data.implementation_at),
        link_video: data.link_video || null,
        link_file_pendukung: data.link_file_pendukung || null,
        ...(isEditing.value ? { _method: 'put' } : {}),
    }));

    if (isEditing.value) {
        transformed.post(route(props.updateRoute, props.innovation.id), { forceFormData: true });

        return;
    }

    transformed.post(route('admin.innovations.store'), { forceFormData: true });
};
</script>

<template>
    <div class="max-w-3xl bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">{{ isEditing ? 'Ubah Inovasi' : 'Ajukan Inovasi' }}</h1>
            <p v-if="isEditing" class="text-sm text-surface-500 mt-1">Slug: {{ innovation.slug }}</p>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="inisiator" class="text-sm font-medium text-surface-700">Jenis Inisiator</label>
                    <Select
                        v-if="!inisiatorLocked"
                        input-id="inisiator"
                        v-model="form.inisiator"
                        :options="inisiatorOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Pilih jenis inisiator"
                        class="w-full"
                    />
                    <InputText v-else id="inisiator" :model-value="inisiatorLabel" disabled class="w-full" />
                    <small v-if="form.errors.inisiator" class="text-rose-600">{{ form.errors.inisiator }}</small>
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
                        :pt="{ pcInputText: { root: { maxlength: 4, inputmode: 'numeric' } } }"
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
                <label for="link_file_pendukung" class="text-sm font-medium text-surface-700">Link File Pendukung</label>
                <InputText
                    id="link_file_pendukung"
                    v-model="form.link_file_pendukung"
                    placeholder="https://drive.google.com/..."
                    class="w-full"
                />
                <small class="text-surface-400">
                    Untuk berkas berukuran besar, unggah ke Google Drive lalu tempel tautannya di sini (pastikan akses "siapa saja dengan tautan" diaktifkan).
                </small>
                <small v-if="form.errors.link_file_pendukung" class="text-rose-600">{{ form.errors.link_file_pendukung }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-surface-700">Cover</span>
                <div v-if="innovation?.cover && !newCoverPreview" class="flex items-center gap-3">
                    <img :src="innovation.cover.thumb" alt="Cover saat ini" class="w-24 h-18 rounded-lg object-cover border border-surface-200" />
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
                <span class="text-sm font-medium text-surface-700">Dokumen pendukung</span>

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
                    @click="router.visit(cancelHref)"
                />
            </div>
        </form>
    </div>
</template>
