import { computed, reactive } from 'vue';

const state = reactive({
    sidebarActive: false, // mobile overlay
    sidebarCollapsed: false, // desktop collapse
});

export function useLayout() {
    const toggleSidebar = () => {
        if (window.innerWidth < 992) {
            state.sidebarActive = !state.sidebarActive;
        } else {
            state.sidebarCollapsed = !state.sidebarCollapsed;
        }
    };

    const closeMobileSidebar = () => {
        state.sidebarActive = false;
    };

    const containerClass = computed(() => ({
        'layout-sidebar-active': state.sidebarActive,
        'layout-sidebar-collapsed': state.sidebarCollapsed,
    }));

    return {
        state,
        toggleSidebar,
        closeMobileSidebar,
        containerClass,
    };
}
