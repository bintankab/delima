import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const NON_FIELD_KEYS = ['isDirty', 'errors', 'hasErrors', 'processing', 'progress', 'wasSuccessful', 'recentlySuccessful'];

/**
 * Drop-in replacement for Inertia's useForm() that clears a field's error
 * as soon as its value changes, instead of leaving stale errors on screen
 * until the next submit.
 */
export function useAutoClearForm(...args) {
    const form = useForm(...args);

    const fieldKeys = Object.keys(form).filter(
        (key) => !NON_FIELD_KEYS.includes(key) && typeof form[key] !== 'function',
    );

    fieldKeys.forEach((key) => {
        watch(() => form[key], () => {
            if (form.errors[key]) {
                form.clearErrors(key);
            }
        });
    });

    return form;
}
