<script setup>
import Accordion from 'primevue/accordion';
import AccordionContent from 'primevue/accordioncontent';
import AccordionHeader from 'primevue/accordionheader';
import AccordionPanel from 'primevue/accordionpanel';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    groups: { type: Array, required: true },
});
</script>

<template>
    <div>
        <section class="bg-indigo-700 text-white">
            <div class="max-w-6xl mx-auto px-4 py-12">
                <h1 class="text-2xl font-bold">FAQ</h1>
                <p class="mt-2 text-indigo-100 max-w-2xl">
                    Pertanyaan yang sering diajukan seputar Delima Kabupaten Bintan.
                </p>
            </div>
        </section>

        <section class="max-w-3xl mx-auto px-4 py-10">
            <p v-if="groups.length === 0" class="text-sm text-white/70">
                Belum ada pertanyaan.
            </p>

            <div v-else class="flex flex-col gap-8">
                <div v-for="(group, index) in groups" :key="index" class="flex flex-col gap-3">
                    <h2 class="text-lg font-semibold text-white">
                        {{ group.category ?? 'Umum' }}
                    </h2>

                    <Accordion multiple class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                        <AccordionPanel v-for="item in group.items" :key="item.id" :value="String(item.id)">
                            <AccordionHeader>{{ item.question }}</AccordionHeader>
                            <AccordionContent>
                                <p class="m-0 whitespace-pre-line text-surface-700">{{ item.answer }}</p>
                            </AccordionContent>
                        </AccordionPanel>
                    </Accordion>
                </div>
            </div>
        </section>
    </div>
</template>
