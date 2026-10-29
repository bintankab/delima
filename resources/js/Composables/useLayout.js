import { computed, reactive } from 'vue';

const state = reactive({
    sidebarActive: false, // mobile overlay
    sidebarCollapsed: false, // desktop collapse
    rightPanelActive: false, // right filter panel overlay
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

    const toggleRightPanel = () => {
        state.rightPanelActive = !state.rightPanelActive;
    };

    const closeRightPanel = () => {
        state.rightPanelActive = false;
    };

    const containerClass = computed(() => ({
        'layout-sidebar-active': state.sidebarActive,
        'layout-sidebar-collapsed': state.sidebarCollapsed,
        'layout-rightpanel-active': state.rightPanelActive,
    }));

    return {
        state,
        toggleSidebar,
        closeMobileSidebar,
        toggleRightPanel,
        closeRightPanel,
        containerClass,
    };
}
