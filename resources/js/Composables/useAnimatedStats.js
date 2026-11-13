import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Animate on first visibility and player changes; edits interpolate from the current values.
export function useAnimatedStats(source, identity = () => null) {
    const root = ref(null);
    const displayed = ref({});
    let frame;
    let observer;
    let visible = false;
    let stopped = false;
    let motion;
    const targets = () => Object.fromEntries(Object.entries(source()).map(([key, value]) =>
        [key, Number.isFinite(Number(value)) ? Number(value) : 0]));

    function animate(reset = false) {
        cancelAnimationFrame(frame);
        const end = targets();
        if (!visible || motion?.matches) { displayed.value = end; return; }
        const start = reset ? {} : { ...displayed.value };
        const began = performance.now();
        const keys = Object.keys(end);
        function tick(now) {
            if (stopped) return;
            let complete = true;
            displayed.value = Object.fromEntries(keys.map((key, index) => {
                const progress = Math.min(1, Math.max(0, (now - began - index * 22) / 600));
                if (progress < 1) complete = false;
                const eased = 1 - (1 - progress) ** 3;
                return [key, (start[key] ?? 0) + (end[key] - (start[key] ?? 0)) * eased];
            }));
            if (!complete) frame = requestAnimationFrame(tick);
        }
        if (reset) displayed.value = Object.fromEntries(keys.map(key => [key, 0]));
        frame = requestAnimationFrame(tick);
    }

    watch(() => [identity(), source()], (current, previous) => animate(current[0] !== previous?.[0]), { deep: true });
    onMounted(() => {
        motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        motion.addEventListener('change', onMotionChange);
        const element = root.value?.$el ?? root.value;
        if (!element || !('IntersectionObserver' in window)) { visible = true; animate(true); return; }
        observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) {
                visible = true;
                observer.disconnect();
                animate(true);
            }
        }, { threshold: 0.1 });
        observer.observe(element);
    });
    function onMotionChange() { if (motion.matches) animate(); }
    onBeforeUnmount(() => {
        stopped = true;
        cancelAnimationFrame(frame);
        observer?.disconnect();
        motion?.removeEventListener('change', onMotionChange);
    });
    const values = computed(() => Object.keys(displayed.value).length ? displayed.value : targets());
    return { root, value: key => Math.round(values.value[key] ?? 0), width: key => `${Math.max(0, Math.min(values.value[key] ?? 0, 100))}%` };
}
