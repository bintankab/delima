<script setup>
import { ref } from 'vue';
import Menu from 'primevue/menu';

defineProps({
    items: { type: Array, required: true },
});

const menu = ref();

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <div class="inline-block">
        <button
            type="button"
            class="w-8 h-8 inline-flex items-center justify-center rounded-full text-surface-500 hover:bg-surface-100 hover:text-surface-700"
            aria-label="Aksi"
            aria-haspopup="true"
            @click="toggle"
        >
            <i class="pi pi-ellipsis-h" />
        </button>
        <Menu ref="menu" :model="items" :popup="true" />
    </div>
</template>
