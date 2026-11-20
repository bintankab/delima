import { ref } from 'vue';

const STORAGE_KEY = 'delima-theme';

const isDark = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('app-dark'));

const readTheme = () => {
    try {
        return localStorage.getItem(STORAGE_KEY) === 'light' ? 'light' : 'dark';
    } catch {
        return 'dark';
    }
};

export const applyPublicTheme = (theme = readTheme()) => {
    const dark = theme !== 'light';

    isDark.value = dark;
    document.documentElement.classList.toggle('app-dark', dark);
};

export function usePublicTheme() {
    applyPublicTheme();

    const toggleTheme = () => {
        const next = isDark.value ? 'light' : 'dark';

        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Keep the choice for this visit when storage is unavailable.
        }

        applyPublicTheme(next);
    };

    return { isDark, toggleTheme };
}
