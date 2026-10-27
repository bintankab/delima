<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ProgressSpinner from 'primevue/progressspinner';

const visible = ref(false);

const mutatingMethods = ['post', 'put', 'patch', 'delete'];

let removeStart;
let removeFinish;

onMounted(() => {
    removeStart = router.on('start', (event) => {
        if (mutatingMethods.includes(event.detail.visit.method)) {
            visible.value = true;
        }
    });

    removeFinish = router.on('finish', () => {
        visible.value = false;
    });
});

onUnmounted(() => {
    removeStart?.();
    removeFinish?.();
});
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-surface-900/20"
    >
        <ProgressSpinner style="width: 3rem; height: 3rem" stroke-width="4" fill="transparent" />
    </div>
</template>
