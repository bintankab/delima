<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import FileUpload from 'primevue/fileupload';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    post: { type: Object, default: null },
    author: { type: String, required: true },
    statuses: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const newCoverPreview = ref(null);

const toDate = (value) => (value ? new Date(`${value}T00:00:00`) : null);

const formatDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return value;
    }

    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${value.getFullYear()}-${month}-${day}`;
};

const form = useAutoClearForm({
    title: props.post?.title ?? '',
    excerpt: props.post?.excerpt ?? '',
    content: props.post?.content ?? '',
    status: props.post?.status ?? 'draft',
    published_at: toDate(props.post?.published_at),
    category_ids: props.post?.category_ids ?? [],
    cover: null,
});

const coverPreview = computed(() => newCoverPreview.value || props.post?.cover?.url || null);

const categoryError = computed(() => {
    const match = Object.entries(form.errors).find(([key]) => key === 'category_ids' || key.startsWith('category_ids.'));

    return match?.[1] ?? null;
});

const initials = computed(() => props.author
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('') || '—');

const onCoverSelect = (event) => {
    const file = event.files?.[0] ?? null;
    form.cover = file;
    newCoverPreview.value = file ? URL.createObjectURL(file) : null;
};

const clearCover = () => {
    form.cover = null;
    newCoverPreview.value = null;
};

const submit = (status) => {
    form.status = status;
    form.transform((data) => ({
        ...data,
        published_at: formatDate(data.published_at),
        _method: props.post ? 'put' : 'post',
    })).post(
        props.post
            ? route('admin.posts.update', props.post.id)
            : route('admin.posts.store'),
        { forceFormData: true },
    );
};

const remove = () => {
    confirm.require({
        message: `Pindahkan post "${props.post.title}" ke sampah?`,
        header: 'Hapus Post',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.posts.destroy', props.post.id)),
    });
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit(form.status)">
        <div>
            <h1 class="text-xl font-semibold text-surface-900">
                {{ post ? 'Ubah Post' : 'Tulis Post' }}
            </h1>
            <p v-if="post?.slug" class="text-sm text-surface-500 mt-1">Slug: {{ post.slug }}</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_20rem] gap-6 items-start">
            <div class="bg-white rounded-xl border border-surface-200 p-5 sm:p-6 flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-surface-700">Sampul</span>
                    <div class="rounded-xl overflow-hidden bg-surface-100 border border-surface-200">
                        <img
                            v-if="coverPreview"
                            :src="coverPreview"
                            alt="Sampul post"
                            class="w-full h-56 object-cover"
                        />
                        <div v-else class="h-40 flex flex-col items-center justify-center gap-2 text-surface-400">
                            <i class="pi pi-image text-3xl" />
                            <span class="text-sm">Belum ada sampul</span>
                        </div>
                    </div>
                    <FileUpload
                        mode="basic"
                        accept="image/jpeg,image/png,image/webp"
                        :max-file-size="10485760"
                        custom-upload
                        choose-label="Pilih sampul"
                        @select="onCoverSelect"
                        @clear="clearCover"
                    />
                    <small v-if="form.errors.cover" class="text-rose-600">{{ form.errors.cover }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="title" class="text-sm font-medium text-surface-700">Judul</label>
                    <InputText id="title" v-model="form.title" class="w-full" autofocus />
                    <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="excerpt" class="text-sm font-medium text-surface-700">Ringkasan</label>
                    <Textarea id="excerpt" v-model="form.excerpt" rows="2" class="w-full" auto-resize />
                    <small class="text-surface-400">Kosongkan untuk mengambil ringkasan dari isi post. Ringkasan ini tampil di beranda.</small>
                    <small v-if="form.errors.excerpt" class="text-rose-600">{{ form.errors.excerpt }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-surface-700">Isi</span>
                    <RichTextEditor v-model="form.content" placeholder="Tulis isi post di sini..." height="320px" />
                    <small v-if="form.errors.content" class="text-rose-600">{{ form.errors.content }}</small>
                </div>
            </div>

            <aside class="bg-white rounded-xl border border-surface-200 p-5 flex flex-col gap-5 xl:sticky xl:top-4">
                <div class="flex gap-2">
                    <Button
                        type="button"
                        label="Simpan Draf"
                        severity="secondary"
                        outlined
                        class="flex-1"
                        :loading="form.processing && form.status === 'draft'"
                        @click="submit('draft')"
                    />
                    <Button
                        type="button"
                        label="Terbitkan"
                        class="flex-1"
                        :loading="form.processing && form.status === 'published'"
                        @click="submit('published')"
                    />
                </div>

                <div class="flex flex-col gap-1.5 border-t border-surface-200 pt-4">
                    <label for="status" class="text-sm font-medium text-surface-700">Status</label>
                    <Select
                        input-id="status"
                        v-model="form.status"
                        :options="statuses"
                        option-label="name"
                        option-value="id"
                        class="w-full"
                    />
                    <small v-if="form.errors.status" class="text-rose-600">{{ form.errors.status }}</small>
                </div>

                <div class="flex flex-col gap-1.5 border-t border-surface-200 pt-4">
                    <label for="published_at" class="text-sm font-medium text-surface-700">Tanggal terbit</label>
                    <DatePicker
                        input-id="published_at"
                        v-model="form.published_at"
                        date-format="yy-mm-dd"
                        show-icon
                        class="w-full"
                    />
                    <small class="text-surface-400">Kosongkan saat menerbitkan untuk memakai tanggal hari ini.</small>
                    <small v-if="form.errors.published_at" class="text-rose-600">{{ form.errors.published_at }}</small>
                </div>

                <div class="flex flex-col gap-2 border-t border-surface-200 pt-4">
                    <span class="text-sm font-medium text-surface-700">Kategori</span>
                    <p v-if="!categories.length" class="text-sm text-surface-400">Belum ada kategori.</p>
                    <div v-else class="flex flex-col gap-2 max-h-48 overflow-y-auto">
                        <label
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center gap-2 text-sm text-surface-800"
                            :style="{ paddingLeft: `${category.depth * 16}px` }"
                        >
                            <Checkbox v-model="form.category_ids" :input-id="`category-${category.id}`" :value="category.id" />
                            {{ category.name }}
                        </label>
                    </div>
                    <small v-if="categoryError" class="text-rose-600">{{ categoryError }}</small>
                </div>

                <div class="flex flex-col gap-1.5 border-t border-surface-200 pt-4">
                    <span class="text-sm font-medium text-surface-700">Penulis</span>
                    <div class="flex items-center gap-2 bg-surface-50 rounded-full px-2 py-1.5 w-fit max-w-full">
                        <span class="w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-semibold flex items-center justify-center shrink-0">
                            {{ initials }}
                        </span>
                        <span class="text-sm text-surface-800 truncate pr-2">{{ author }}</span>
                    </div>
                </div>

                <div v-if="post" class="border-t border-surface-200 pt-4">
                    <Button
                        type="button"
                        label="Pindahkan ke sampah"
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        class="w-full"
                        @click="remove"
                    />
                </div>
            </aside>
        </div>
    </form>
</template>
