<script setup>
import { computed, nextTick, ref } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import FileUpload from 'primevue/fileupload';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    height: { type: String, default: '200px' },
});

const emit = defineEmits(['update:modelValue']);

const innerValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

let quill = null;
let savedRange = null;

const dialogVisible = ref(false);
const libraryItems = ref([]);
const libraryLoading = ref(false);
const libraryLoaded = ref(false);
const libraryNextUrl = ref(null);
const uploading = ref(false);
const uploadError = ref('');

const onEditorLoad = (event) => {
    quill = event.instance;
    quill.getModule('toolbar').addHandler('image', openMediaDialog);
};

const openMediaDialog = () => {
    if (quill) {
        savedRange = quill.getSelection(true);
    }

    uploadError.value = '';
    dialogVisible.value = true;

    if (!libraryLoaded.value) {
        loadLibrary();
    }
};

const loadLibrary = async (url = null) => {
    libraryLoading.value = true;

    try {
        const response = await axios.get(url ?? route('admin.media-library.index'));
        libraryItems.value = url ? [...libraryItems.value, ...response.data.data] : response.data.data;
        libraryNextUrl.value = response.data.next_page_url;
        libraryLoaded.value = true;
    } finally {
        libraryLoading.value = false;
    }
};

const insertImage = (url) => {
    if (!quill) {
        return;
    }

    const range = savedRange ?? { index: quill.getLength() };

    // Close the dialog before moving focus back into the editor — doing it the
    // other way around fights PrimeVue's focus trap mid-transition and leaves
    // the dialog stuck open.
    dialogVisible.value = false;

    nextTick(() => {
        quill.insertEmbed(range.index, 'image', url, 'user');
        quill.setSelection(range.index + 1);
    });
};

const onFileSelect = async (event) => {
    const file = event.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post(route('admin.media-library.store'), formData);
        libraryItems.value = [response.data, ...libraryItems.value];
        insertImage(response.data.url);
    } catch (error) {
        uploadError.value = error.response?.data?.errors?.file?.[0] ?? 'Gagal mengunggah gambar.';
    } finally {
        uploading.value = false;
    }
};
</script>

<template>
    <Editor
        v-model="innerValue"
        :placeholder="placeholder"
        :editor-style="`height: ${height}`"
        @load="onEditorLoad"
    />

    <Dialog
        v-model:visible="dialogVisible"
        header="Pilih Gambar"
        modal
        dismissable-mask
        :style="{ width: '40rem', maxWidth: '95vw' }"
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <FileUpload
                    mode="basic"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    :max-file-size="5242880"
                    custom-upload
                    choose-label="Unggah Gambar Baru"
                    :disabled="uploading"
                    @select="onFileSelect"
                />
                <small v-if="uploading" class="text-surface-500">Mengunggah...</small>
                <small v-if="uploadError" class="text-rose-600">{{ uploadError }}</small>
            </div>

            <div>
                <p class="text-sm font-medium text-surface-700 mb-2">Atau pilih dari pustaka media</p>

                <div v-if="libraryLoading && libraryItems.length === 0" class="text-sm text-surface-400">
                    Memuat pustaka media...
                </div>
                <div v-else-if="libraryItems.length === 0" class="text-sm text-surface-400">
                    Belum ada gambar diunggah.
                </div>
                <div v-else class="grid grid-cols-4 gap-2 max-h-80 overflow-y-auto">
                    <button
                        v-for="item in libraryItems"
                        :key="item.id"
                        type="button"
                        class="aspect-square rounded-lg overflow-hidden border border-surface-200 hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :title="item.name"
                        @click="insertImage(item.url)"
                    >
                        <img :src="item.thumb || item.url" :alt="item.name" class="w-full h-full object-cover" />
                    </button>
                </div>

                <div v-if="libraryNextUrl" class="mt-2 text-center">
                    <Button
                        label="Muat lebih banyak"
                        severity="secondary"
                        text
                        size="small"
                        :loading="libraryLoading"
                        @click="loadLibrary(libraryNextUrl)"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>
