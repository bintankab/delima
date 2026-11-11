<script setup>
import { router } from '@inertiajs/vue3';
import { useAutoClearForm } from '@/Composables/useAutoClearForm';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    faq: { type: Object, default: null },
});

const form = useAutoClearForm({
    question: props.faq?.question ?? '',
    answer: props.faq?.answer ?? '',
    category: props.faq?.category ?? '',
    order: props.faq?.order ?? 0,
});

const submit = () => {
    if (props.faq) {
        form.put(route('admin.faqs.update', props.faq.id));

        return;
    }

    form.post(route('admin.faqs.store'));
};
</script>

<template>
    <div class="bg-white rounded-xl border border-surface-200 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-surface-900">
                {{ faq ? 'Ubah FAQ' : 'Tambah FAQ' }}
            </h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="question" class="text-sm font-medium text-surface-700">Pertanyaan</label>
                <InputText id="question" v-model="form.question" class="w-full" autofocus />
                <small v-if="form.errors.question" class="text-rose-600">{{ form.errors.question }}</small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="answer" class="text-sm font-medium text-surface-700">Jawaban</label>
                <Textarea id="answer" v-model="form.answer" rows="5" class="w-full" auto-resize />
                <small v-if="form.errors.answer" class="text-rose-600">{{ form.errors.answer }}</small>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="category" class="text-sm font-medium text-surface-700">Kategori</label>
                    <InputText id="category" v-model="form.category" class="w-full" />
                    <small v-if="form.errors.category" class="text-rose-600">{{ form.errors.category }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="order" class="text-sm font-medium text-surface-700">Urutan</label>
                    <InputNumber
                        input-id="order"
                        v-model="form.order"
                        :min="0"
                        :use-grouping="false"
                        class="w-full"
                    />
                    <small v-if="form.errors.order" class="text-rose-600">{{ form.errors.order }}</small>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" label="Simpan" :loading="form.processing" />
                <Button
                    type="button"
                    label="Batal"
                    severity="secondary"
                    @click="router.visit(route('admin.faqs.index'))"
                />
            </div>
        </form>
    </div>
</template>
