<script setup>
import { Link } from '@inertiajs/vue3';
import { useMenu } from '@/Composables/useMenu';

const { sections } = useMenu();

const isActive = (routeName) => {
    if (route().current(routeName)) {
        return true;
    }

    if (!routeName.endsWith('.index')) {
        return false;
    }

    return route().current(`${routeName.slice(0, -'.index'.length)}.*`);
};
</script>

<template>
    <aside class="app-sidebar">
        <nav class="app-menu">
            <div v-for="section in sections" :key="section.label" class="app-menu-section">
                <p class="app-menu-section-label">{{ section.label }}</p>
                <ul>
                    <li v-for="item in section.items" :key="item.route">
                        <Link
                            :href="route(item.route)"
                            class="app-menu-item"
                            :class="{ 'app-menu-item-active': isActive(item.route) }"
                        >
                            <i :class="item.icon" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>
</template>
