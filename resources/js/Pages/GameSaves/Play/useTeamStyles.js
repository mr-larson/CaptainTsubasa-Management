import { usePage } from '@inertiajs/vue3';

export function useTeamStyles() {
    const styles = usePage().props.teamStyles ?? {
        tactical:   { labels: {}, icons: {} },
        philosophy: { labels: {}, icons: {} },
    };

    const tacticalLabel = (key) => styles.tactical.labels[key] ?? key ?? '—';
    const tacticalIcon  = (key) => styles.tactical.icons[key]  ?? '';

    const philosophyLabel = (key) => styles.philosophy.labels[key] ?? key ?? '—';
    const philosophyIcon  = (key) => styles.philosophy.icons[key]  ?? '';

    // Couleurs Tailwind par style tactique
    const tacticalColor = (key) => ({
        offensive:  'bg-rose-100    text-rose-700    border-rose-200',
        defensive:  'bg-blue-100    text-blue-700    border-blue-200',
        possession: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        counter:    'bg-amber-100   text-amber-700   border-amber-200',
        balanced:   'bg-slate-100   text-slate-700   border-slate-200',
    }[key] ?? 'bg-slate-100 text-slate-700 border-slate-200');

    // Couleurs par philosophie
    const philosophyColor = (key) => ({
        stars:      'bg-yellow-100  text-yellow-700  border-yellow-200',
        collective: 'bg-teal-100    text-teal-700    border-teal-200',
        balanced:   'bg-violet-100  text-violet-700  border-violet-200',
        economist:  'bg-slate-100   text-slate-600   border-slate-300',
    }[key] ?? 'bg-slate-100 text-slate-600 border-slate-300');

    return {
        tacticalLabel, tacticalIcon, tacticalColor,
        philosophyLabel, philosophyIcon, philosophyColor,
    };
}
