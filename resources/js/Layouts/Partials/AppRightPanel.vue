<script setup>
import { useLayout } from '@/Composables/useLayout';

const { closeRightPanel } = useLayout();
</script>

<template>
    <aside class="app-rightpanel">
        <div class="app-rightpanel-header">
            <h2 class="text-sm font-semibold text-surface-900">Filter</h2>
            <button type="button" class="app-rightpanel-close" aria-label="Tutup panel filter" @click="closeRightPanel">
                <i class="pi pi-times" />
            </button>
        </div>
        <div id="app-rightpanel-body" class="app-rightpanel-body" />
    </aside>
</template>
