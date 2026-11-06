<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    page: { type: Object, default: null },
    author: { type: String, required: true },
    statuses: { type: Array, required: true },
    layouts: { type: Array, required: true },
});

const confirm = useConfirm();

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
    title: props.page?.title ?? '',
    excerpt: props.page?.excerpt ?? '',
    content: props.page?.content ?? '',
    status: props.page?.status ?? 'draft',
    published_at: toDate(props.page?.published_at),
    layout: props.page?.layout ?? 'layout_1',
    cards: (props.page?.cards ?? []).map((card) => ({
        title: card.title,
        body: card.body,
        image_id: card.image_id,
        image: null,
        preview: card.image_url,
    })),
    slides: (props.page?.slides ?? []).map((slide) => ({
        id: slide.id,
        caption: slide.caption,
        url: slide.url,
    })),
    new_slides: [],
    new_slide_captions: [],
});

const newSlides = ref([]);

const selectedLayout = computed(() => props.layouts.find((layout) => layout.id === form.layout) ?? null);

const fieldError = (prefix) => {
    const match = Object.entries(form.errors).find(([key]) => key === prefix || key.startsWith(`${prefix}.`));

    return match?.[1] ?? null;
};

const addCard = () => {
    form.cards.push({ title: '', body: '', image_id: null, image: null, preview: null });
};

const removeCard = (index) => {
    form.cards.splice(index, 1);
};

const onCardImage = (index, event) => {
    const file = event.target.files?.[0] ?? null;
    form.cards[index].image = file;
    form.cards[index].preview = file ? URL.createObjectURL(file) : form.cards[index].preview;
};

const clearCardImage = (index) => {
    form.cards[index].image = null;
    form.cards[index].image_id = null;
    form.cards[index].preview = null;
};

const addSlide = (event) => {
    const file = event.target.files?.[0] ?? null;
    event.target.value = '';
    if (!file) {
        return;
    }

    newSlides.value.push({
        file,
        caption: '',
        preview: URL.createObjectURL(file),
    });
};

const removeNewSlide = (index) => {
    newSlides.value.splice(index, 1);
};

const removeSlide = (index) => {
    form.slides.splice(index, 1);
};

const moveSlide = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= form.slides.length) {
        return;
    }

    const [item] = form.slides.splice(index, 1);
    form.slides.splice(target, 0, item);
};

const submit = (status) => {
    form.status = status;
    form.new_slides = newSlides.value.map((slide) => slide.file);
    form.new_slide_captions = newSlides.value.map((slide) => slide.caption);
    form.transform((data) => ({
        ...data,
        published_at: formatDate(data.published_at),
        cards: data.cards.map((card) => ({
            title: card.title,
            body: card.body,
            image_id: card.image_id,
            image: card.image,
        })),
        slides: data.slides.map((slide) => ({
            id: slide.id,
            caption: slide.caption,
        })),
        _method: props.page ? 'put' : 'post',
    })).post(
        props.page
            ? route('admin.pages.update', props.page.id)
            : route('admin.pages.store'),
        { forceFormData: true },
    );
};

const remove = () => {
    confirm.require({
        message: `Pindahkan page "${props.page.title}" ke sampah?`,
        header: 'Hapus Page',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('admin.pages.destroy', props.page.id)),
    });
};
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit(form.status)">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-white">
                    {{ page ? 'Ubah Page' : 'Tulis Page' }}
                </h1>
                <p v-if="page?.slug" class="text-sm text-white/70 mt-1">Alamat publik: /p/{{ page.slug }}</p>
            </div>
            <Button
                v-if="page"
                type="button"
                label="Hapus"
                icon="pi pi-trash"
                severity="danger"
                text
                @click="remove"
            />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_20rem] gap-6 items-start">
            <div class="bg-white rounded-xl border border-surface-200 p-5 sm:p-6 flex flex-col gap-5">
                <div class="flex flex-col gap-1.5">
                    <label for="title" class="text-sm font-medium text-surface-700">Judul</label>
                    <InputText id="title" v-model="form.title" class="w-full" autofocus />
                    <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="excerpt" class="text-sm font-medium text-surface-700">Ringkasan</label>
                    <Textarea id="excerpt" v-model="form.excerpt" rows="2" class="w-full" auto-resize />
                    <small class="text-surface-400">Kosongkan untuk mengambil ringkasan dari isi halaman.</small>
                    <small v-if="form.errors.excerpt" class="text-rose-600">{{ form.errors.excerpt }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-surface-700">Isi</span>
                    <RichTextEditor v-model="form.content" placeholder="Tulis isi halaman di sini..." height="280px" />
                    <small v-if="form.errors.content" class="text-rose-600">{{ form.errors.content }}</small>
                </div>

                <div v-if="selectedLayout?.has_cards" class="flex flex-col gap-3 border-t border-surface-200 pt-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-surface-700">Kartu</span>
                        <Button type="button" label="Tambah kartu" icon="pi pi-plus" size="small" severity="secondary" @click="addCard" />
                    </div>
                    <p v-if="!form.cards.length" class="text-sm text-surface-400">Belum ada kartu.</p>
                    <div
                        v-for="(card, index) in form.cards"
                        :key="index"
                        class="rounded-xl border border-surface-200 p-4 flex flex-col gap-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-surface-500">Kartu {{ index + 1 }}</span>
                            <Button type="button" label="Hapus" size="small" severity="danger" text @click="removeCard(index)" />
                        </div>
                        <InputText v-model="card.title" placeholder="Judul kartu" class="w-full" />
                        <Textarea v-model="card.body" rows="3" placeholder="Isi kartu" class="w-full" auto-resize />
                        <div class="flex items-center gap-3">
                            <img v-if="card.preview" :src="card.preview" alt="" class="h-16 w-24 rounded-lg object-cover bg-surface-100" />
                            <input type="file" accept="image/jpeg,image/png,image/webp" @change="onCardImage(index, $event)" />
                            <Button v-if="card.preview" type="button" label="Lepas gambar" size="small" severity="secondary" text @click="clearCardImage(index)" />
                        </div>
                    </div>
                    <small v-if="fieldError('cards')" class="text-rose-600">{{ fieldError('cards') }}</small>
                </div>

                <div v-if="selectedLayout?.has_slides" class="flex flex-col gap-3 border-t border-surface-200 pt-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-surface-700">Slideshow</span>
                        <label class="inline-flex">
                            <input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="addSlide" />
                            <span class="inline-flex items-center gap-2 rounded-md border border-surface-300 px-3 py-1.5 text-sm text-surface-700 cursor-pointer">
                                <i class="pi pi-plus text-xs" />
                                Tambah gambar
                            </span>
                        </label>
                    </div>
                    <p v-if="!form.slides.length && !newSlides.length" class="text-sm text-surface-400">Belum ada gambar slideshow.</p>
                    <div
                        v-for="(slide, index) in form.slides"
                        :key="slide.id"
                        class="rounded-xl border border-surface-200 p-4 flex flex-col sm:flex-row gap-3 sm:items-center"
                    >
                        <img :src="slide.url" alt="" class="h-16 w-24 rounded-lg object-cover bg-surface-100" />
                        <InputText v-model="slide.caption" placeholder="Keterangan" class="flex-1" />
                        <div class="flex gap-1">
                            <Button type="button" icon="pi pi-arrow-up" severity="secondary" text :disabled="index === 0" @click="moveSlide(index, -1)" />
                            <Button type="button" icon="pi pi-arrow-down" severity="secondary" text :disabled="index === form.slides.length - 1" @click="moveSlide(index, 1)" />
                            <Button type="button" icon="pi pi-trash" severity="danger" text @click="removeSlide(index)" />
                        </div>
                    </div>
                    <div
                        v-for="(slide, index) in newSlides"
                        :key="slide.preview"
                        class="rounded-xl border border-dashed border-surface-300 p-4 flex flex-col sm:flex-row gap-3 sm:items-center"
                    >
                        <img :src="slide.preview" alt="" class="h-16 w-24 rounded-lg object-cover bg-surface-100" />
                        <InputText v-model="slide.caption" placeholder="Keterangan" class="flex-1" />
                        <Button type="button" icon="pi pi-trash" severity="danger" text @click="removeNewSlide(index)" />
                    </div>
                    <small v-if="fieldError('new_slides') || fieldError('slides')" class="text-rose-600">{{ fieldError('new_slides') || fieldError('slides') }}</small>
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
                    <label for="layout" class="text-sm font-medium text-surface-700">Layout</label>
                    <Select
                        input-id="layout"
                        v-model="form.layout"
                        :options="layouts"
                        option-label="name"
                        option-value="id"
                        class="w-full"
                    />
                    <small class="text-surface-400">Dua halaman boleh memakai layout yang sama. Isinya tetap milik masing-masing halaman.</small>
                    <small v-if="form.errors.layout" class="text-rose-600">{{ form.errors.layout }}</small>
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

                <p class="text-sm text-surface-500 border-t border-surface-200 pt-4">Penulis: {{ author }}</p>
            </aside>
        </div>
    </form>
</template>
