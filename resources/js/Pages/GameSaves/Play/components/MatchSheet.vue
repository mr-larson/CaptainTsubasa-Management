<script setup>
import { computed } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import { usePlayerUtils } from '../usePlayerUtils.js';
import { isGoalEvent, eventKind } from '../matchEvents.js';

/**
 * Feuille de match des deux équipes : titulaires, remplaçants, entrées/sorties,
 * buts, cartons, blessures, arrêts, capitaine, homme du match.
 *
 * Sources : match_stats.starters / playtime / players / events / lineups
 * (instantané) + incidents (cartons et blessures, joints par l'API stats).
 */
const props = defineProps({
    match:       { type: Object, required: true },
    stats:       { type: Object, required: true },                // match_stats (+ incidents)
    rosters:     { type: Object, required: true },                // { home: [...], away: [...] }
    teamNames:   { type: Object, default: () => ({ home: '', away: '' }) },
    formations:  { type: Object, default: () => ({ home: null, away: null }) },
    motmId:      { type: [Number, String], default: null },
});

const emit = defineEmits(['show-player']);

const { playerPhotoUrl, positionGroup } = usePlayerUtils();

const GROUP_ORDER = { GK: 0, DEF: 1, MID: 2, ATT: 3, OTHER: 4 };
const sideOfEvent = (ev) => (ev?.team === 'internal' ? 'home' : 'away');
const norm = (s) => String(s ?? '').trim().toLowerCase();

const totalTurns = computed(() => Math.max(0, ...Object.values(props.stats.playtime ?? {}).map(Number)));

// Équipe d'un joueur d'après son nom (le champ `team` des événements de
// remplacement / carton suit la possession du ballon, pas le joueur concerné).
const sideOfName = (name) => {
    const n = norm(name);
    for (const side of ['home', 'away']) {
        if ((props.rosters[side] ?? []).some(p => norm(p.lastname) === n)) return side;
    }
    return null;
};

// Remplacements du déroulé : « 🔄 Victorino → Murashige » (matchs joués)
const substitutions = computed(() => (props.stats.events ?? [])
    .filter(ev => eventKind(ev) === 'substitution')
    .map(ev => {
        const text = (ev.details ?? []).find(d => typeof d === 'string' && /→|->/.test(d)) ?? '';
        const [out, inn] = text.replace(/^🔄\s*/u, '').split(/\s*(?:→|->)\s*/);
        const side = sideOfName(out) ?? sideOfName(inn) ?? sideOfEvent(ev);
        return { side, turn: ev.turn, out: out?.trim(), in: inn?.trim() };
    })
    .filter(s => s.out && s.in));

// Tour d'un carton dans le déroulé (« 🟨 Taro Misaki — Carton jaune »)
const cardTurn = (player) => {
    const ev = (props.stats.events ?? []).find(e =>
        /card/.test(eventKind(e)) && (e.details ?? []).some(d => norm(d).includes(norm(player.lastname))));
    return ev?.turn ?? null;
};

const CARD_ICON = { yellow: '🟨', red: '🟥', double_yellow: '🟨🟥' };

function buildSide(side) {
    const roster   = props.rosters[side] ?? [];
    const byId     = new Map(roster.map(p => [String(p.id), p]));
    const starters = (props.stats.starters?.[side] ?? []).map(String);
    const playtime = props.stats.playtime ?? {};
    const pstats   = props.stats.players ?? {};
    const goals    = (props.stats.events ?? []).filter(isGoalEvent);
    const subs     = substitutions.value.filter(s => s.side === side);
    const hasSubEvents = substitutions.value.length > 0;

    const rowFor = (p, isStarter) => {
        const pid  = String(p.id);
        const mins = playtime[pid] ?? playtime[p.id] ?? null;
        const st   = pstats[pid] ?? {};
        const d    = st.defense ?? {};

        const myGoals = goals
            .filter(ev => (ev.attacker?.id ? String(ev.attacker.id) === pid : norm(ev.attacker?.name).endsWith(norm(p.lastname))))
            .map(ev => ({ turn: ev.turn, special: /special/i.test(ev.actionType ?? '') || /spécial/i.test(ev.text ?? '') }));
        // Repli (anciens matchs sans acteur) : compteur de buts des stats joueurs
        const goalList = myGoals.length ? myGoals
            : Array.from({ length: st.offense?.goals ?? 0 }, () => ({ turn: null, special: false }));

        let outTurn = subs.find(s => norm(s.out) === norm(p.lastname))?.turn ?? null;
        let inTurn  = subs.find(s => norm(s.in)  === norm(p.lastname))?.turn ?? null;
        if (!hasSubEvents && mins !== null && totalTurns.value) {
            if (isStarter && mins > 0 && mins < totalTurns.value) outTurn = mins;
            if (!isStarter && mins > 0) inTurn = totalTurns.value - mins;
        }

        const cards = (props.stats.incidents?.sanctions ?? [])
            .filter(s => String(s.player_id) === pid)
            .map(s => ({ icon: CARD_ICON[s.type] ?? '🟨', turn: cardTurn(p),
                         title: s.weeks_suspended ? `Suspendu ${s.weeks_suspended} semaine(s)` : 'Carton' }));
        const injury = (props.stats.incidents?.injuries ?? []).find(i => String(i.player_id) === pid) ?? null;
        const saves  = (d.hands?.success ?? 0) + (d.punch?.success ?? 0) + (d.gkSpecial?.success ?? 0);
        const group  = positionGroup(p.position);

        return {
            p, pid, group, starter: isStarter, mins,
            used: isStarter || (mins ?? 0) > 0 || inTurn !== null,
            goals: goalList, cards, injury,
            saves: group === 'GK' ? saves : 0,
            outTurn, inTurn,
            captain: !!p.is_captain,
            motm: String(props.motmId) === pid,
            slot: p.slot ?? null,
        };
    };

    // Titulaires : poste enregistré (slot) si connu, sinon ordre des lignes.
    const starterRows = starters
        .map(id => byId.get(id) ?? { id, lastname: '?', position: '' })
        .map(p => rowFor(p, true))
        .sort((a, b) => (a.slot ?? 99) - (b.slot ?? 99) || GROUP_ORDER[a.group] - GROUP_ORDER[b.group]);

    const benchRows = roster
        .filter(p => !starters.includes(String(p.id)))
        .map(p => rowFor(p, false))
        .sort((a, b) => (b.used - a.used) || (a.inTurn ?? 99) - (b.inTurn ?? 99) || GROUP_ORDER[a.group] - GROUP_ORDER[b.group]);

    return {
        side,
        name: props.teamNames[side],
        formation: props.formations[side],
        starters: starterRows,
        bench: benchRows,
        subs,
    };
}

const sides = computed(() => [buildSide('home'), buildSide('away')]);
const hasStarters = computed(() => sides.value.some(s => s.starters.length));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <div v-if="hasStarters" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="s in sides" :key="s.side" class="min-w-0">
                <!-- En-tête d'équipe -->
                <div class="flex items-baseline justify-between gap-2 pb-1.5 mb-2 border-b-2"
                     :class="s.side === 'home' ? 'border-blue-400' : 'border-orange-400'">
                    <span class="text-sm font-black truncate" :class="s.side === 'home' ? 'text-blue-700' : 'text-orange-600'">
                        {{ s.side === 'home' ? '🏠' : '✈️' }} {{ s.name }}
                    </span>
                    <span v-if="s.formation" class="text-[11px] font-bold text-slate-500 shrink-0">{{ s.formation }}</span>
                </div>

                <template v-for="block in [
                    { key: 'starters', label: 'Titulaires', rows: s.starters },
                    { key: 'bench',    label: 'Remplaçants', rows: s.bench },
                ]" :key="block.key">
                    <div v-if="block.rows.length" class="mb-3">
                        <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ block.label }}</div>
                        <button v-for="r in block.rows" :key="r.pid" type="button"
                                @click="emit('show-player', s.side, r.pid)"
                                class="w-full flex items-center gap-2 px-1.5 py-1 rounded-md text-left text-xs hover:bg-white transition-colors"
                                :class="r.used ? '' : 'opacity-45'"
                                title="Voir la fiche du joueur">
                            <span class="w-5 text-right font-mono text-2xs text-slate-400 shrink-0">{{ r.p.number ?? '—' }}</span>
                            <span class="w-5 h-5 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <PlayerPhoto :src="playerPhotoUrl(r.p)" class="w-full h-full object-cover" alt="" />
                            </span>
                            <span class="font-semibold text-slate-700 truncate">{{ r.p.lastname }}</span>
                            <span v-if="r.captain" class="shrink-0 px-1 rounded bg-amber-400 text-3xs font-black text-slate-700" title="Capitaine">C</span>
                            <span v-if="r.motm" class="shrink-0" title="Homme du match">⭐</span>
                            <span class="shrink-0 text-3xs font-bold text-slate-400">{{ { GK: 'GK', DEF: 'DEF', MID: 'MIL', ATT: 'ATT' }[r.group] ?? '' }}</span>

                            <span class="ml-auto flex flex-wrap justify-end items-center gap-1 text-2xs text-slate-500">
                                <span v-for="(g, i) in r.goals" :key="'g' + i" class="whitespace-nowrap" :title="g.special ? 'But (geste spécial)' : 'But'">
                                    ⚽<template v-if="g.turn"> T{{ g.turn }}</template><span v-if="g.special" class="text-amber-600">★</span>
                                </span>
                                <span v-for="(c, i) in r.cards" :key="'c' + i" class="whitespace-nowrap" :title="c.title">
                                    {{ c.icon }}<template v-if="c.turn"> T{{ c.turn }}</template>
                                </span>
                                <span v-if="r.injury" :title="`${r.injury.description ?? 'Blessure'} — ${r.injury.weeks_out} semaine(s)`">🤕</span>
                                <span v-if="r.saves" class="text-violet-600 font-semibold" title="Arrêts">🧤 {{ r.saves }}</span>
                                <span v-if="r.outTurn !== null" class="text-rose-500 whitespace-nowrap" title="Sorti">⬇ T{{ r.outTurn }}</span>
                                <span v-if="r.inTurn !== null" class="text-emerald-600 whitespace-nowrap" title="Entré en jeu">⬆ T{{ r.inTurn }}</span>
                                <span v-if="!r.used" class="italic">non entré</span>
                            </span>
                        </button>
                    </div>
                </template>

                <!-- Changements -->
                <div v-if="s.subs.length" class="pt-2 border-t border-slate-200 space-y-0.5">
                    <div v-for="(c, i) in s.subs" :key="i" class="text-[11px] text-slate-500">
                        🔄 <span class="font-mono text-slate-400">T{{ c.turn }}</span>
                        <span class="text-rose-500">{{ c.out }}</span> ➜ <span class="text-emerald-600">{{ c.in }}</span>
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="text-xs text-slate-500 italic text-center py-6">
            La composition de ce match n'a pas été enregistrée.
        </p>
        <p v-if="hasStarters && !stats.lineups" class="mt-3 text-2xs text-slate-500 italic">
            Match antérieur à l'enregistrement des effectifs : les remplaçants sont ceux de l'effectif actuel.
        </p>
    </div>
</template>
