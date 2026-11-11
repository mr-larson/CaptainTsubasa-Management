// Filtres de recrutement partagés (Draft, Transferts) : recherche, poste,
// univers d'origine, prix maximum et tri.
import { computed, ref, toValue, watch } from 'vue';
import { POSITION_GROUPS, positionGroup } from '@/Enums/positionBadges.js';
import { compareRecruits } from './recruitment.js';

export const ORIGIN_OPTIONS = [
    { key: 'captain_tsubasa',     label: 'Captain Tsubasa' },
    { key: 'blue_lock',           label: 'Blue Lock' },
    { key: 'hungry_heart',        label: 'Hungry Heart' },
    { key: 'ecole_des_champions', label: "L'École des Champions" },
    { key: 'ao_ashi',             label: 'Ao Ashi' },
    { key: 'original',            label: 'Originaux' },
    { key: 'amateur',             label: 'Amateurs' },
];

/**
 * @param players   liste (ref, computed ou getter) des joueurs recrutables
 * @param cost      (player) => prix comparé au maximum et utilisé par le tri « coût »
 * @param overall   (player) => note globale (tri « note globale »)
 * @param origins   (ref/getter, optionnel) options d'univers visibles ; null = pas de filtre d'univers
 */
export function useRecruitFilters(players, { cost, overall, defaultSort = 'name', origins = null }) {
    const search = ref('');
    const position = ref('ALL');
    const origin = ref('ALL');
    const sort = ref(defaultSort);
    const maxPrice = ref('');

    const all = computed(() => toValue(players) ?? []);

    const originOptions = computed(() => origins ? [{ key: 'ALL', label: 'Toutes' }, ...toValue(origins)] : []);
    if (origins) {
        watch(originOptions, (options) => {
            if (!options.some(({ key }) => key === origin.value)) origin.value = 'ALL';
        });
    }

    const originCount = (key) => key === 'ALL' ? all.value.length : all.value.filter(p => p.origin === key).length;
    const originScoped = computed(() => origin.value === 'ALL' ? all.value : all.value.filter(p => p.origin === origin.value));

    const positionCount = (key) => key === 'ALL'
        ? originScoped.value.length
        : originScoped.value.filter(p => positionGroup(p.position) === key).length;

    const positionOptions = computed(() => [
        { key: 'ALL', label: 'Tous', count: positionCount('ALL') },
        ...POSITION_GROUPS.map(group => ({ key: group.key, label: group.plural, count: positionCount(group.key) })),
    ]);

    const filtered = computed(() => {
        let list = originScoped.value;
        if (position.value !== 'ALL') list = list.filter(p => positionGroup(p.position) === position.value);
        const query = search.value.trim().toLowerCase();
        if (query) {
            list = list.filter(p => p.lastname?.toLowerCase().includes(query) || p.firstname?.toLowerCase().includes(query));
        }
        if (maxPrice.value !== '' && maxPrice.value != null) list = list.filter(p => cost(p) <= Number(maxPrice.value));
        return [...list].sort((a, b) => compareRecruits(a, b, sort.value, cost, overall));
    });

    const hasActiveFilters = computed(() => !!search.value.trim() || position.value !== 'ALL'
        || origin.value !== 'ALL' || (maxPrice.value !== '' && maxPrice.value != null));

    return {
        search, position, origin, sort, maxPrice,
        filtered, hasActiveFilters,
        positionOptions, originOptions: computed(() => originOptions.value.map(o => ({ ...o, count: originCount(o.key) }))),
    };
}
