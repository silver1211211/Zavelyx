import { reactive } from 'vue';

const state = reactive({ toasts: [] });
let uid = 1;

export function useToast() {
    function push({ type = 'info', title = '', message = '', duration = 4500 }) {
        const id = uid++;
        state.toasts.push({ id, type, title, message, duration, createdAt: Date.now() });
        if (duration > 0) setTimeout(() => dismiss(id), duration);
        return id;
    }

    function dismiss(id) {
        const i = state.toasts.findIndex(t => t.id === id);
        if (i !== -1) state.toasts.splice(i, 1);
    }

    return {
        toasts: state.toasts,
        push,
        dismiss,
        success: (title, message, duration) => push({ type: 'success', title, message, duration }),
        error:   (title, message, duration) => push({ type: 'error',   title, message, duration }),
        warning: (title, message, duration) => push({ type: 'warning', title, message, duration }),
        info:    (title, message, duration) => push({ type: 'info',    title, message, duration }),
    };
}
