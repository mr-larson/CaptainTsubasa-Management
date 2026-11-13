<script setup>
import Modal from '@/Components/Modal.vue';
import PlayerDetailContent from './components/PlayerDetailContent.vue';
import PixelLoader from '@/Components/PixelLoader.vue';
import TabBar from '@/Components/TabBar.vue';
import { fieldClass } from '@/Components/fieldStyles';
import { computed, ref, watch } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import MatchReplayPitch from './MatchReplayPitch.vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import MatchProgression from './components/MatchProgression.vue';
import MatchPlayersTable from './components/MatchPlayersTable.vue';
import MatchSheet from './components/MatchSheet.vue';
import { isGoalEvent, eventKind, eventIcon, eventOutcome } from './matchEvents.js';

const props = defineProps({
    calendarTeams:                 { type: Array,    required: true },
    calendarTeam:                  { type: Object,   default: null },
    calendarRows:                  { type: Array,    required: true },
    calendarTeamRoster:            { type: Array,    required: true },
    calendarOpponentRoster:        { type: Array, default: () => [] },
    teamById:                      { type: Object,   required: true },
    selectedCalendarMatch:         { type: Object,   default: null },
    selectedCalendarMatchStats:    { type: Object,   default: null },
    selectedCalendarMyTeamStats:   { type: Object,   default: null },
    selectedCalendarOpponentStats: { type: Object,   default: null },
    selectedCalendarPlayersStats:  { type: Object,   required: true },
    isByeMatch:                    { type: Function, required: true },
    opponentNameForTeam:           { type: Function, required: true },
    selectedCalendarProgression: { type: Array, default: () => [] },
    selectedCalendarEvents:      { type: Array, default: () => [] },
    // id du match dont le détail est en cours de chargement
    loadingMatchId:              { type: [Number, String], default: null },
    myTeamId:                    { type: [Number, String], default: null },
});

const emit = defineEmits(['select-team', 'open-match-stats']);

// ==========================
//   NAVIGATION ENTRE LES MATCHS JOUÉS DE L'ÉQUIPE SUIVIE
// ==========================
const playedMatches = computed(() =>
    props.calendarRows.filter(m => !props.isByeMatch(m) && m.status === 'played' && m.has_stats)
);
const currentIndex = computed(() => playedMatches.value.findIndex(m => m.id === props.selectedCalendarMatch?.id));
const prevMatch = computed(() => currentIndex.value > 0 ? playedMatches.value[currentIndex.value - 1] : null);
const nextMatch = computed(() => currentIndex.value >= 0 ? playedMatches.value[currentIndex.value + 1] ?? null : null);

const openMatch = (m) => { if (m) emit('open-match-stats', m); };
const onJump = (id) => openMatch(playedMatches.value.find(m => String(m.id) === String(id)));

function onTeamChange(id) {
    const t = props.calendarTeams.find(team => Number(team.id) === Number(id));
    if (t) emit('select-team', t);
}

const navLabel = (m) => {
    const opp  = props.opponentNameForTeam(m, props.calendarTeam?.id);
    const home = m.home_team_id === props.calendarTeam?.id;
    const s    = home ? `${m.home_score}-${m.away_score}` : `${m.away_score}-${m.home_score}`;
    return `${roundShort(m)} · ${home ? '🏠' : '✈️'} ${opp} · ${s}`;
};

// Sans match sélectionné (arrivée sur l'onglet, changement d'équipe) : dernier match joué.
watch(() => [props.calendarTeam?.id, props.selectedCalendarMatch?.id], () => {
    if (props.selectedCalendarMatch || props.loadingMatchId) return;
    const last = playedMatches.value[playedMatches.value.length - 1];
    if (last) emit('open-match-stats', last);
}, { immediate: true });

// ── Onglets de la fiche de match ──────────────────────────────
const detailTab = ref('sheet');
const detailTabs = computed(() => {
    const tabs = [
        { key: 'sheet',   label: '📝 Feuille de match' },
        { key: 'replay',  label: '▶ Revoir le match' },
        { key: 'stats',   label: '📊 Stats' },
        { key: 'players', label: '👥 Joueurs' },
    ];
    if (props.selectedCalendarEvents.length) tabs.push({ key: 'log', label: '📋 Déroulé', count: props.selectedCalendarEvents.length });
    if (props.selectedCalendarProgression.length) tabs.push({ key: 'progression', label: '📈 Progression', count: props.selectedCalendarProgression.length });
    return tabs;
});
// Onglet indisponible sur ce match (pas de déroulé / de progression) → Stats
watch(detailTabs, (tabs) => {
    if (!tabs.some(t => t.key === detailTab.value)) detailTab.value = 'sheet';
});

// ==========================
//   HELPERS
// ==========================
const { playerPhotoUrl, teamLogoUrl } = usePlayerUtils();

// Libellé court de la colonne gauche : tour de Coupe du Monde (poule / ½ / F)
// si le match porte un `round`, sinon la semaine de ligue (S1, S2…).
const ROUND_SHORT = {
    group_a: 'Pl.A', group_b: 'Pl.B', group_c: 'Pl.C', group_d: 'Pl.D',
    semi: '½', final: 'F',
};
const roundShort = (match) => ROUND_SHORT[match?.round] ?? `S${match?.week}`;
const roundTitle = (match) => ({
    group_a: 'Poule A', group_b: 'Poule B', group_c: 'Poule C', group_d: 'Poule D',
    semi: 'Demi-finale', final: 'Finale',
}[match?.round] ?? `Semaine ${match?.week}`);

const homeTeamName = computed(() =>
    props.selectedCalendarMatch ? props.teamById[props.selectedCalendarMatch.home_team_id]?.name : ''
);
const awayTeamName = computed(() =>
    props.selectedCalendarMatch ? props.teamById[props.selectedCalendarMatch.away_team_id]?.name : ''
);

// Effectif + formation d'une équipe pour la reconstitution du replay
const replaySquad = (teamId) => {
    const team = props.teamById[teamId];
    if (!team) return null;
    const players = (team.contracts ?? [])
        .map(c => {
            const p = c.game_player ?? c.gamePlayer ?? null;
            return p ? {
                id:         p.id,
                lastname:   p.lastname,
                number:     p.number,
                position:   p.position,
                is_starter: c.is_starter ?? false,
            } : null;
        })
        .filter(Boolean);
    return { formation: team.formation ?? null, players };
};

// Instantané des effectifs au moment du match (match_stats.lineups) : un joueur
// parti depuis reste dans l'historique, une recrue n'y apparaît pas. Les matchs
// joués avant l'instantané retombent sur l'effectif actuel.
const lineupSnapshot = (side) => props.selectedCalendarMatchStats?.lineups?.[side] ?? null;

const matchRoster = (side) => {
    const snap = lineupSnapshot(side);
    if (snap) return snap.players ?? [];
    const teamId = side === 'home' ? props.selectedCalendarMatch?.home_team_id : props.selectedCalendarMatch?.away_team_id;
    return teamId === props.calendarTeam?.id ? props.calendarTeamRoster : props.calendarOpponentRoster;
};

const replayHomeSquad = computed(() =>
    props.selectedCalendarMatch ? (lineupSnapshot('home') ?? replaySquad(props.selectedCalendarMatch.home_team_id)) : null
);
const replayAwaySquad = computed(() =>
    props.selectedCalendarMatch ? (lineupSnapshot('away') ?? replaySquad(props.selectedCalendarMatch.away_team_id)) : null
);

// ── Déroulé du match enrichi (logs structurés) ───────────────
const playersById = computed(() => {
    const map = {};
    for (const team of Object.values(props.teamById ?? {})) {
        for (const c of team?.contracts ?? []) {
            const p = c.game_player ?? c.gamePlayer ?? null;
            if (p) map[p.id] = p;
        }
    }
    return map;
});

const eventPlayerPhoto = (ref) => {
    if (!ref?.id) return null;
    const p = playersById.value[ref.id];
    return p ? playerPhotoUrl(p) : null;
};

const EVENT_ACTION_LABELS = {
    pass: 'Passe', dribble: 'Dribble', shot: 'Tir', special: 'Spécial',
    cross: 'Centre', long_pass: 'Passe longue',
    intercept: 'Interception', tackle: 'Tacle', block: 'Contre', heading: 'Tête',
    hands: 'Arrêt ✋', punch: 'Poings 👊', 'gk-special': 'Arrêt spécial',
    'field-special': 'Défense spéciale',
};
const eventActionLabel = (key) => EVENT_ACTION_LABELS[key] ?? null;

// Filtres du déroulé
const logFilter = ref('all');
const LOG_FILTERS = [
    { key: 'all',      label: 'Tout' },
    { key: 'goals',    label: '⚽ Buts' },
    { key: 'shots',    label: '🎯 Tirs' },
    { key: 'heroics',  label: '🔥 Temps forts' },
];

const isGoalEv  = isGoalEvent;
const isShotEv  = (ev) => isGoalEv(ev) || eventKind(ev) === 'shot' || ev?.action === 'shot';
// Temps forts : buts, expulsions, blessures et gestes héroïques / coups spéciaux.
const HIGHLIGHT_KINDS = ['goal', 'card-red', 'injury', 'heroic'];
const isHeroEv  = (ev) => HIGHLIGHT_KINDS.includes(eventKind(ev)) || /héroï|dépassement|reroll|spécial/i.test(ev?.text ?? '');

// Événements annotés du score courant, puis filtrés (affichage antichronologique)
const annotatedEvents = computed(() => {
    let home = 0, away = 0;
    return props.selectedCalendarEvents.map((ev, index) => {
        if (isGoalEv(ev)) {
            if (ev.team === 'internal') home++; else away++;
        }
        return { ...ev, index, score: isGoalEv(ev) ? `${home} - ${away}` : null };
    });
});

const LOG_FILTER_FN = {
    all:     () => true,
    goals:   isGoalEv,
    shots:   isShotEv,
    heroics: isHeroEv,
};
const logFilterCount = (key) => annotatedEvents.value.filter(LOG_FILTER_FN[key]).length;

// Ordre chronologique par défaut (on lit le match du coup d'envoi au coup de sifflet).
const newestFirst = ref(false);

const filteredLogEvents = computed(() => {
    const list = annotatedEvents.value.filter(LOG_FILTER_FN[logFilter.value] ?? LOG_FILTER_FN.all);
    return newestFirst.value ? [...list].reverse() : list;
});

// Détail des dés d'un duel (logs du moteur joué : ev.meta = { attack, defense, result, … })
const expandedEvent = ref(null);
watch(() => props.selectedCalendarMatch?.id, () => { expandedEvent.value = null; });

const fmt = (n) => (Number.isFinite(Number(n)) ? Number(n).toFixed(1) : '—');
const diceBreakdown = (ev) => {
    const m = ev?.meta;
    if (!m?.attack || !m?.defense) return null;
    const side = (s) => ({
        total: fmt(s.total),
        base:  fmt(s.base),
        additions: (s.additions ?? []).map(a => `${a.label} ${a.value}`),
        tired: Number(s.staminaFactor ?? 1) < 1 ? `fatigue ×${Number(s.staminaFactor).toFixed(2)}` : null,
    });
    return {
        attack:  side(m.attack),
        defense: side(m.defense),
        winner:  m.result?.winner === 'attack' ? 'attaque' : m.result?.winner === 'defense' ? 'défense' : null,
        diff:    fmt(Math.abs(m.result?.diff ?? 0)),
        rule:    m.result?.bonusRuleLabel ?? null,
        crit:    m.result?.critWinner ?? null,
        reroll:  !!m.captainReroll,
    };
};
const toggleEvent = (ev) => {
    if (!diceBreakdown(ev)) return;
    expandedEvent.value = expandedEvent.value === ev.index ? null : ev.index;
};

// Résultat d'un match pour une équipe donnée
const matchResult = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome  = match.home_team_id === teamId;
    const scored  = isHome ? (match.home_score ?? 0) : (match.away_score ?? 0);
    const against = isHome ? (match.away_score ?? 0) : (match.home_score ?? 0);
    if (scored > against)  return { label: 'V', bg: 'bg-emerald-500', text: 'text-white' };
    if (scored === against) return { label: 'N', bg: 'bg-slate-300',   text: 'text-slate-700' };
    return { label: 'D', bg: 'bg-rose-500', text: 'text-white' };
};

const scoreFor = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome = match.home_team_id === teamId;
    const s = isHome ? match.home_score : match.away_score;
    const a = isHome ? match.away_score : match.home_score;
    return { scored: s ?? 0, against: a ?? 0 };
};

const opponentTeam = (match, teamId) => {
    if (!match || !teamId) return null;
    const oppId = match.home_team_id === teamId ? match.away_team_id : match.home_team_id;
    return props.teamById[oppId] ?? null;
};

const selectedStatsTeam = ref('home');

const statsTeamId = computed(() => {
    if (!props.selectedCalendarMatch) return null;
    return selectedStatsTeam.value === 'home'
        ? props.selectedCalendarMatch.home_team_id
        : props.selectedCalendarMatch.away_team_id;
});

const statsTeamName = computed(() => {
    if (!props.selectedCalendarMatch) return '—';
    return props.teamById[statsTeamId.value]?.name ?? '—';
});

// Comparatif équipes, dans l'ordre de l'en-tête : domicile à gauche, extérieur à droite.
// (Après le passage d'un duel, le gagnant de l'un est le perdant de l'autre :
// « duels gagnés » suffit.)
const statBars = computed(() => {
    const t = props.selectedCalendarMatchStats?.teams;
    const m = props.selectedCalendarMatch;
    if (!t?.home || !t?.away || !m) return [];
    const row = (label, home, away) => ({ label, home: Number(home ?? 0), away: Number(away ?? 0) });
    return [
        row('Buts',          m.home_score,      m.away_score),
        row('Tirs',          t.home.shots,      t.away.shots),
        row('Passes',        t.home.passes,     t.away.passes),
        row('Centres', teamActionAttempts('home', 'offense.cross'), teamActionAttempts('away', 'offense.cross')),
        row('Dribbles',      t.home.dribbles,   t.away.dribbles),
        row('Têtes défensives', teamActionAttempts('home', 'defense.heading'), teamActionAttempts('away', 'defense.heading')),
        row('Duels gagnés',  t.home.duelsWon,   t.away.duelsWon),
        row('Arrêts',        teamSaves('home'), teamSaves('away')),
    ];
});

const statBarWidth = (val, other) => {
    const total = val + other;
    if (!total) return 50;
    return Math.round((val / total) * 100);
};

// Stats joueur dans le match sélectionné
const playerMatchStat = (playerId, path) => {
    const s = props.selectedCalendarPlayersStats?.[playerId]
        ?? props.selectedCalendarPlayersStats?.[String(playerId)];
    if (!s) return 0;
    return path.split('.').reduce((o, k) => o?.[k] ?? 0, s);
};
const isGK = (p) => p?.position?.toLowerCase().includes('goalkeeper');

// Arrêts réussis d'un gardien (mains + poings + arrêt spécial)
const playerSaves = (playerId) =>
    playerMatchStat(playerId, 'defense.hands.success')
    + playerMatchStat(playerId, 'defense.punch.success')
    + playerMatchStat(playerId, 'defense.gkSpecial.success');

// Arrêts d'une équipe : match_stats.teams n'a pas de clé `saves`, on somme ses joueurs.
const teamSaves = (side) => matchRoster(side).reduce((sum, p) => sum + playerSaves(p.id), 0);
const teamActionAttempts = (side, action) =>
    matchRoster(side).reduce((sum, p) => sum + playerMatchStat(p.id, `${action}.attempts`), 0);
// ==========================
//   DÉROULÉ DU MATCH (action par action)
//   Alimenté par `match_stats.events`, produit aussi bien par le moteur
//   client (state.matchLog → buildMatchStats) que par MatchSimulator (PHP),
//   afin d'afficher le même play-by-play pour les matchs joués et simulés.
// ==========================
// Icône et fond d'une ligne : communs aux matchs simulés et joués (cf. matchEvents.js)
const playLogIcon = eventIcon;

function playLogRowClass(ev) {
    if (isGoalEvent(ev))                     return 'bg-emerald-100/80 border border-emerald-200';
    if (eventOutcome(ev) === 'success')      return 'bg-white/70';
    if (eventOutcome(ev) === 'failed')       return 'bg-slate-100/70';
    return 'bg-white/50';
}

const replayEvents = computed(() => {
    const stats = props.selectedCalendarMatchStats;
    if (!stats) return [];
    const players = stats.players ?? {};
    const events  = [];

    for (const [pid, pStats] of Object.entries(players)) {
        const homeRoster = matchRoster('home');
        const player = [...homeRoster, ...matchRoster('away')].find(p => String(p.id) === String(pid));
        if (!player) continue;

        const teamSide = homeRoster.some(p => String(p.id) === String(pid)) ? 'home' : 'away';

        const goals = pStats.offense?.goals ?? 0;
        for (let i = 0; i < goals; i++) {
            events.push({ type: 'goal', teamSide, priority: 0, icon: '⚽', color: 'bg-emerald-500', text: `⚽ But de ${player.lastname} !`, detail: null });
        }

        const hands = pStats.defense?.hands?.attempts ?? 0;
        const punch = pStats.defense?.punch?.attempts ?? 0;
        const saves = hands + punch;
        if (saves > 0 && isGK(player)) {
            const savesSuccess = (pStats.defense?.hands?.success ?? 0) + (pStats.defense?.punch?.success ?? 0);
            events.push({ type: 'save', teamSide, priority: 1, icon: '🧤', color: 'bg-violet-500', text: `${player.lastname} : ${savesSuccess} arrêt(s) sur ${saves} tir(s)`, detail: `Mains ${hands} · Poings ${punch}` });
        }

        const shotAttempts = pStats.offense?.shot?.attempts ?? 0;
        const shotSuccess  = pStats.offense?.shot?.success  ?? 0;
        if (shotAttempts > 0 && !isGK(player)) {
            events.push({ type: 'shot', teamSide, priority: 2, icon: '🔥', color: 'bg-orange-400', text: `${player.lastname} : ${shotAttempts} tir(s)`, detail: shotSuccess > 0 ? `${shotSuccess} cadré(s)` : 'Aucun cadré' });
        }

        const passAttempts = pStats.offense?.pass?.attempts ?? 0;
        const passSuccess  = pStats.offense?.pass?.success  ?? 0;
        if (passSuccess >= 1) {
            events.push({ type: 'pass', teamSide, priority: 3, icon: '🎯', color: 'bg-sky-400', text: `${player.lastname} : ${passSuccess}/${passAttempts} passes`, detail: null });
        }

        const dribAttempts = pStats.offense?.dribble?.attempts ?? 0;
        const dribSuccess  = pStats.offense?.dribble?.success  ?? 0;
        if (dribSuccess >= 1) {
            events.push({ type: 'dribble', teamSide, priority: 3, icon: '🌀', color: 'bg-yellow-400', text: `${player.lastname} : ${dribSuccess}/${dribAttempts} dribbles`, detail: null });
        }

        const interceptAttempts = pStats.defense?.intercept?.attempts ?? 0;
        const intercepts        = pStats.defense?.intercept?.success  ?? 0;
        if (interceptAttempts >= 1) {
            events.push({ type: 'intercept', teamSide, priority: 4, icon: '🛡️', color: 'bg-emerald-400', text: `${player.lastname} : ${intercepts}/${interceptAttempts} interception(s)`, detail: null });
        }

        const tackleAttempts = pStats.defense?.tackle?.attempts ?? 0;
        const tackles        = pStats.defense?.tackle?.success  ?? 0;
        if (tackleAttempts >= 1) {
            events.push({ type: 'tackle', teamSide, priority: 4, icon: '⚡', color: 'bg-amber-400', text: `${player.lastname} : ${tackles}/${tackleAttempts} tacle(s)`, detail: null });
        }

        const blockAttempts = pStats.defense?.block?.attempts ?? 0;
        const blocks        = pStats.defense?.block?.success  ?? 0;
        if (blockAttempts >= 1) {
            events.push({ type: 'block', teamSide, priority: 4, icon: '🧱', color: 'bg-slate-500', text: `${player.lastname} : ${blocks}/${blockAttempts} bloc(s)`, detail: null });
        }

        const duelsWon  = pStats.duelsWon  ?? 0;
        const duelsLost = pStats.duelsLost ?? 0;
        if (duelsWon > 0) {
            events.push({ type: 'duels', teamSide, priority: 5, icon: '⚔️', color: 'bg-teal-500', text: `${player.lastname} : ${duelsWon} duel(s) gagné(s)`, detail: `${duelsLost} perdu(s)` });
        }
    }

    return events.sort((a, b) => a.priority - b.priority);
});

// ==========================
//   RÉSUMÉ (buteurs, homme du match, temps forts)
// ==========================
const lastName = (ref) => (ref?.id && playersById.value[ref.id]?.lastname) || (ref?.name ?? '').split(' ').pop() || '?';

const sideOfPlayer = (pid) => {
    if (matchRoster('home').some(p => String(p.id) === String(pid))) return 'home';
    if (matchRoster('away').some(p => String(p.id) === String(pid))) return 'away';
    return null;
};

const rosterName = (pid) =>
    [...matchRoster('home'), ...matchRoster('away')].find(p => String(p.id) === String(pid))?.lastname
    ?? playersById.value[pid]?.lastname ?? '?';

// Buteurs : depuis le déroulé (avec le tour), sinon depuis les stats joueurs.
const scorers = computed(() => {
    const goals = annotatedEvents.value.filter(isGoalEv).map(ev => ({
        side:    ev.team === 'internal' ? 'home' : 'away',
        turn:    ev.turn,
        name:    ev.attacker ? lastName(ev.attacker) : (ev.text?.match(/BUT\s*!\s*(.+?)\s+marque/i)?.[1] ?? '?'),
        special: /special/i.test(ev.actionType ?? '') || /spécial/i.test(ev.text ?? ''),
    }));
    if (goals.length) return goals;

    const out = [];
    for (const [pid, st] of Object.entries(props.selectedCalendarPlayersStats ?? {})) {
        const n = st?.offense?.goals ?? 0;
        const side = sideOfPlayer(pid);
        for (let i = 0; i < n && side; i++) out.push({ side, turn: null, name: rosterName(pid), special: false });
    }
    return out;
});
const scorersBySide = (side) => scorers.value.filter(g => g.side === side);

// Homme du match : note simple sur les stats du match.
const manOfTheMatch = computed(() => {
    let best = null;
    for (const [pid, st] of Object.entries(props.selectedCalendarPlayersStats ?? {})) {
        const o = st?.offense ?? {}, d = st?.defense ?? {};
        const saves = (d.hands?.success ?? 0) + (d.punch?.success ?? 0) + (d.gkSpecial?.success ?? 0);
        const goals = o.goals ?? 0;
        const score = goals * 4
            + (o.shot?.success ?? 0) + (o.dribble?.success ?? 0) + (o.pass?.success ?? 0) * 0.5
            + (d.intercept?.success ?? 0) + (d.tackle?.success ?? 0) + (d.block?.success ?? 0)
            + saves * 1.5
            + (st?.duelsWon ?? 0) - (st?.duelsLost ?? 0) * 0.5;
        if (!best || score > best.score || (score === best.score && goals > best.goals)) {
            best = { pid, score, goals, saves, duelsWon: st?.duelsWon ?? 0,
                     dribbles: o.dribble?.success ?? 0, side: sideOfPlayer(pid) };
        }
    }
    if (!best || best.score <= 0) return null;
    const bits = [];
    if (best.goals)    bits.push(`${best.goals} but${best.goals > 1 ? 's' : ''}`);
    if (best.saves)    bits.push(`${best.saves} arrêt${best.saves > 1 ? 's' : ''}`);
    if (best.dribbles) bits.push(`${best.dribbles} dribble${best.dribbles > 1 ? 's' : ''} réussi${best.dribbles > 1 ? 's' : ''}`);
    if (best.duelsWon) bits.push(`${best.duelsWon} duel${best.duelsWon > 1 ? 's' : ''} gagné${best.duelsWon > 1 ? 's' : ''}`);
    const player = [...matchRoster('home'), ...matchRoster('away')].find(p => String(p.id) === String(best.pid))
        ?? playersById.value[best.pid] ?? null;
    return {
        pid:    best.pid,
        name:   rosterName(best.pid),
        player,
        team:   best.side ? props.teamById[best.side === 'home' ? props.selectedCalendarMatch?.home_team_id : props.selectedCalendarMatch?.away_team_id]?.name : null,
        detail: bits.slice(0, 3).join(', '),
    };
});

// Temps forts : cartons, blessures, gestes héroïques, gros matchs de gardien.
const highlights = computed(() => {
    const list = [];
    for (const ev of props.selectedCalendarEvents) {
        const kind = eventKind(ev);
        const detail = (ev.details ?? []).find(d => typeof d === 'string') ?? '';
        if (kind === 'card-red' || kind === 'card-yellow' || kind === 'injury') {
            list.push({ icon: eventIcon(ev), text: (detail || ev.text || '').replace(/^[🟨🟥🤕]\s*/u, ''), turn: ev.turn });
        } else if (kind === 'heroic') {
            list.push({ icon: '⭐', text: ev.text, turn: ev.turn });
        }
    }
    for (const side of ['home', 'away']) {
        for (const p of matchRoster(side)) {
            const n = playerSaves(p.id);
            if (n >= 3) list.push({ icon: '🧤', text: `${p.lastname} : ${n} arrêts`, turn: null });
        }
    }
    return list.slice(0, 6);
});

const logSection = ref(null);
const selectedPlayer = ref(null);
// Même fiche détaillée que dans le calendrier et le palmarès.
const showPlayer = (side, playerId) => {
    const snapshotPlayer = matchRoster(side).find(p => String(p.id) === String(playerId));
    if (!snapshotPlayer) return;
    const teamId = side === 'home' ? props.selectedCalendarMatch?.home_team_id : props.selectedCalendarMatch?.away_team_id;
    const team = props.teamById[teamId];
    const currentPlayer = [...props.calendarTeamRoster, ...props.calendarOpponentRoster,
        ...(team?.contracts ?? []).map(c => c.game_player ?? c.gamePlayer).filter(Boolean)]
        .find(p => String(p.id) === String(playerId));
    selectedPlayer.value = { ...currentPlayer, ...snapshotPlayer, teamName: team?.name };
};
watch(() => props.selectedCalendarMatch?.id, () => { selectedPlayer.value = null; });


const homeTeamEvents = computed(() => replayEvents.value.filter(e => e.teamSide === 'home'));
const awayTeamEvents = computed(() => replayEvents.value.filter(e => e.teamSide === 'away'));
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- Navigation : équipe suivie, match précédent / suivant, accès direct -->
        <div v-if="calendarTeam" class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-wrap items-center gap-2">
            <div class="w-8 h-8 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                <TeamLogoImage v-if="teamLogoUrl(calendarTeam)" :src="teamLogoUrl(calendarTeam)" class="w-full h-full" alt=""/>
            </div>
            <select :value="calendarTeam.id" @change="onTeamChange($event.target.value)"
                    aria-label="Équipe suivie"
                    class="min-w-[220px] pl-3 pr-9 py-1.5 cursor-pointer" :class="fieldClass({ shape: 'rect' })">
                <option v-for="t in calendarTeams" :key="t.id" :value="t.id">
                    {{ t.name }}{{ Number(t.id) === Number(myTeamId) ? ' (mon équipe)' : '' }}
                </option>
            </select>

            <div class="flex items-center gap-1 ml-auto">
                <button type="button" :disabled="!prevMatch" @click="openMatch(prevMatch)"
                        class="px-3 py-1.5 rounded-lg text-sm font-bold border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Match précédent">◀</button>
                <select :value="selectedCalendarMatch?.id ?? ''" @change="onJump($event.target.value)"
                        aria-label="Aller au match"
                        class="min-w-[260px] pl-3 pr-9 py-1.5 cursor-pointer" :class="fieldClass({ shape: 'rect' })">
                    <option v-if="!selectedCalendarMatch" value="" disabled>Choisir un match</option>
                    <option v-for="m in playedMatches" :key="m.id" :value="m.id">{{ navLabel(m) }}</option>
                </select>
                <button type="button" :disabled="!nextMatch" @click="openMatch(nextMatch)"
                        class="px-3 py-1.5 rounded-lg text-sm font-bold border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Match suivant">▶</button>
            </div>
        </div>

        <div v-if="selectedCalendarMatch && selectedCalendarMatchStats" class="flex flex-col gap-3">
            <!-- Résumé et navigation forment un seul en-tête. -->
            <div>
            <div class="border border-b-0 border-slate-200 rounded-t-xl bg-slate-50 p-4 pb-3">
                <div class="flex flex-wrap items-center justify-around gap-4">

                    <!-- Équipe domicile -->
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                            <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                 :src="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                 class="w-full h-full" alt=""/>
                        </div>
                        <div class="text-xs font-semibold text-slate-700 text-center truncate max-w-[80px]">
                            {{ teamById[selectedCalendarMatch?.home_team_id]?.name }}
                        </div>
                    </div>

                    <!-- Score -->
                    <div class="text-center shrink-0">
                        <div class="text-3xl font-black text-slate-700">
                            {{ selectedCalendarMatch.home_score }}
                            <span class="text-slate-300 mx-1">-</span>
                            {{ selectedCalendarMatch.away_score }}
                        </div>
                        <div class="text-2xs text-slate-400 font-semibold mt-1">{{ roundTitle(selectedCalendarMatch) }}</div>
                    </div>

                    <!-- Équipe extérieure -->
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                            <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                 :src="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                 class="w-full h-full" alt=""/>
                        </div>
                        <div class="text-xs font-semibold text-slate-700 text-center truncate max-w-[80px]">
                            {{ teamById[selectedCalendarMatch?.away_team_id]?.name }}
                        </div>
                    </div>
                </div>

                <!-- Buteurs -->
                <div v-if="scorers.length" aria-label="Buteurs du match" class="mt-1 grid grid-cols-2 gap-6 text-[11px]">
                    <div class="text-right space-y-0.5">
                        <div v-for="(g, i) in scorersBySide('home')" :key="'h' + i" class="text-slate-600">
                            <span class="font-semibold">{{ g.name }}</span><span v-if="g.special" class="text-amber-600"> (spécial)</span>
                            <span v-if="g.turn" class="text-slate-400"> T{{ g.turn }}</span> ⚽
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <div v-for="(g, i) in scorersBySide('away')" :key="'a' + i" class="text-slate-600">
                            ⚽ <span class="font-semibold">{{ g.name }}</span><span v-if="g.special" class="text-amber-600"> (spécial)</span>
                            <span v-if="g.turn" class="text-slate-400"> T{{ g.turn }}</span>
                        </div>
                    </div>
                </div>

                <!-- Homme du match + temps forts -->
                <div v-if="manOfTheMatch || highlights.length" class="mt-3 pt-3 border-t border-slate-200 space-y-2">
                    <div v-if="manOfTheMatch" class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs">
                        <div class="w-7 h-7 rounded-full overflow-hidden bg-amber-100 ring-2 ring-amber-300 shrink-0">
                            <PlayerPhoto v-if="manOfTheMatch.player" :src="playerPhotoUrl(manOfTheMatch.player)" class="w-full h-full object-cover" alt="" />
                        </div>
                        <span class="font-bold text-amber-700 whitespace-nowrap">⭐ Homme du match :</span>
                        <span class="font-semibold text-slate-700">{{ manOfTheMatch.name }}</span>
                        <span v-if="manOfTheMatch.team" class="text-slate-400">({{ manOfTheMatch.team }})</span>
                        <span v-if="manOfTheMatch.detail" class="text-slate-500">— {{ manOfTheMatch.detail }}</span>
                    </div>
                    <div v-if="highlights.length" class="flex flex-wrap gap-1.5">
                        <span v-for="(h, i) in highlights" :key="i"
                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white border border-slate-200 text-2xs font-semibold text-slate-600">
                            {{ h.icon }} {{ h.text }}<span v-if="h.turn" class="text-slate-400">· T{{ h.turn }}</span>
                        </span>
                    </div>
                </div>


            </div>

            <!-- Onglets intégrés au résumé, avec ouverture vers le contenu. -->
            <TabBar v-model="detailTab" :tabs="detailTabs" size="sm" outlined label="Fiche de match"
                    style="background-color: #f8fafc; border-radius: 0 0 0.75rem 0.75rem; padding-left: 1.5rem" />
            </div>

            <!-- La relecture est montée uniquement dans son onglet pour arrêter la lecture à sa fermeture. -->
            <section v-if="detailTab === 'replay'" :key="selectedCalendarMatch.id"
                     aria-label="Relecture du match" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
    <!-- Replay visuel basé sur le déroulé action par action (engine.js / MatchSimulator) -->
    <MatchReplayPitch
        v-if="selectedCalendarEvents.length"
        :events="selectedCalendarEvents"
        :home-name="teamById[selectedCalendarMatch.home_team_id]?.name ?? 'Domicile'"
        :away-name="teamById[selectedCalendarMatch.away_team_id]?.name ?? 'Extérieur'"
        :home-logo="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
        :away-logo="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
        :final-home-score="selectedCalendarMatch.home_score"
        :final-away-score="selectedCalendarMatch.away_score"
        :home-squad="replayHomeSquad"
        :away-squad="replayAwaySquad"
    />

    <!-- Repli : ancien résumé statique (matchs sans 'events' enregistrés) -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <!-- HOME -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded overflow-hidden bg-slate-100 shrink-0">
                    <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                         :src="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                         class="w-full h-full" alt=""/>
                </div>
                <span class="text-xs font-black text-slate-700">
                        {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                    </span>
                <span class="text-lg font-black text-emerald-600 ml-auto">
                        {{ selectedCalendarMatch.home_score }}
                    </span>
            </div>

            <div v-if="homeTeamEvents.length" class="space-y-2">
                <div v-for="(ev, i) in homeTeamEvents" :key="i"
                     class="flex items-start gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[11px] shrink-0"
                         :class="ev.color">
                        {{ ev.icon }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-700">{{ ev.text }}</div>
                        <div v-if="ev.detail" class="text-2xs text-slate-400">{{ ev.detail }}</div>
                    </div>
                </div>
            </div>
            <p v-else class="text-xs text-slate-500 italic">Aucun événement notable.</p>
        </div>

        <!-- AWAY -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded overflow-hidden bg-slate-100 shrink-0">
                    <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                         :src="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                         class="w-full h-full" alt=""/>
                </div>
                <span class="text-xs font-black text-slate-700">
                        {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                    </span>
                <span class="text-lg font-black text-emerald-600 ml-auto">
                        {{ selectedCalendarMatch.away_score }}
                    </span>
            </div>

            <div v-if="awayTeamEvents.length" class="space-y-2">
                <div v-for="(ev, i) in awayTeamEvents" :key="i"
                     class="flex items-start gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[11px] shrink-0"
                         :class="ev.color">
                        {{ ev.icon }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-700">{{ ev.text }}</div>
                        <div v-if="ev.detail" class="text-2xs text-slate-400">{{ ev.detail }}</div>
                    </div>
                </div>
            </div>
            <p v-else class="text-xs text-slate-500 italic">Aucun événement notable.</p>
        </div>
    </div>

    <!-- Stats équipe résumé -->
    <div class="mt-4 pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4 text-center text-xs">
        <div class="flex justify-around text-slate-500">
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.home?.shots ?? 0 }}</div><div>Tirs</div></div>
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.home?.passes ?? 0 }}</div><div>Passes</div></div>
            <div><div class="font-black text-slate-700">{{ teamSaves('home') }}</div><div>Arrêts</div></div>
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.home?.duelsWon ?? 0 }}</div><div>D+</div></div>
        </div>
        <div class="flex justify-around text-slate-500">
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.away?.shots ?? 0 }}</div><div>Tirs</div></div>
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.away?.passes ?? 0 }}</div><div>Passes</div></div>
            <div><div class="font-black text-slate-700">{{ teamSaves('away') }}</div><div>Arrêts</div></div>
            <div><div class="font-black text-slate-700">{{ selectedCalendarMatchStats?.teams?.away?.duelsWon ?? 0 }}</div><div>D+</div></div>
        </div>
    </div>
            </section>

            <!-- Feuille de match -->
            <MatchSheet v-show="detailTab === 'sheet'"
                        :match="selectedCalendarMatch"
                        :stats="selectedCalendarMatchStats"
                        :rosters="{ home: matchRoster('home'), away: matchRoster('away') }"
                        :teamNames="{ home: homeTeamName, away: awayTeamName }"
                        :formations="{ home: lineupSnapshot('home')?.formation ?? null, away: lineupSnapshot('away')?.formation ?? null }"
                        :motmId="manOfTheMatch?.pid ?? null"
                        @show-player="showPlayer" />

            <!-- Barres comparatives équipes -->
            <div v-show="detailTab === 'stats'" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-3">Comparatif équipes</h4>
                <div class="space-y-2">
                    <div v-for="bar in statBars" :key="bar.label">
                        <div class="text-2xs text-slate-400 text-center mb-0.5">{{ bar.label }}</div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 text-right text-xs font-black"
                                 :class="bar.home > bar.away ? 'text-blue-600' : 'text-slate-500'">{{ bar.home }}</div>
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden flex">
                                <div class="h-full bg-blue-500 transition-all"
                                     :style="{ width: statBarWidth(bar.home, bar.away) + '%' }"></div>
                                <div class="h-full bg-orange-400 transition-all"
                                     :style="{ width: statBarWidth(bar.away, bar.home) + '%' }"></div>
                            </div>
                            <div class="w-8 text-left text-xs font-black"
                                 :class="bar.away > bar.home ? 'text-orange-500' : 'text-slate-500'">{{ bar.away }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-[11px] font-semibold">
                    <span class="text-blue-600">🏠 {{ homeTeamName }}</span>
                    <span class="text-orange-500">{{ awayTeamName }} ✈️</span>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- BLOC : Progression des joueurs               -->
            <!-- ============================================ -->
            <MatchProgression v-show="detailTab === 'progression'" :progression="selectedCalendarProgression" :home-team-name="homeTeamName" :away-team-name="awayTeamName" />

            <!-- Joueurs -->
            <div v-show="detailTab === 'players'">
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">
                        Joueurs — {{ statsTeamName }}
                    </h4>
                    <div class="flex rounded-lg overflow-hidden border border-slate-200 text-[11px] font-semibold">
                        <button type="button" @click="selectedStatsTeam = 'home'"
                                class="px-3 py-1 transition-all"
                                :class="selectedStatsTeam === 'home' ? 'bg-teal-500 text-white' : 'bg-white text-slate-500 hover:bg-slate-50'">
                            {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                        </button>
                        <button type="button" @click="selectedStatsTeam = 'away'"
                                class="px-3 py-1 transition-all border-l border-slate-300"
                                :class="selectedStatsTeam === 'away' ? 'bg-teal-500 text-white' : 'bg-white text-slate-500 hover:bg-slate-50'">
                            {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                        </button>
                    </div>
                </div>
                <MatchPlayersTable :players="matchRoster(selectedStatsTeam)"
                                   :stats="selectedCalendarPlayersStats"
                                   :starters="selectedCalendarMatchStats?.starters?.[selectedStatsTeam] ?? null"
                                   :playtime="selectedCalendarMatchStats?.playtime ?? null"
                                   :motmId="manOfTheMatch?.pid ?? null" />
            </div>
            </div>

            <!-- ============================================ -->
            <!-- BLOC : Déroulé du match (action par action)  -->
            <!-- ============================================ -->
            <div v-if="selectedCalendarEvents.length" v-show="detailTab === 'log'" ref="logSection" class="border border-slate-200 rounded-xl bg-slate-50 p-4 scroll-mt-2">
                <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
                    <h4 class="text-2xs font-bold text-slate-500 uppercase tracking-wider">
                        📋 Déroulé du match (action par action)
                    </h4>
                    <div class="flex items-center gap-1.5">
                        <button v-for="f in LOG_FILTERS" :key="f.key" type="button"
                                @click="logFilter = f.key"
                                class="px-2 py-0.5 rounded-full text-2xs font-semibold border transition-all"
                                :class="logFilter === f.key
                                    ? 'bg-teal-500 text-white border-teal-600'
                                    : 'bg-white text-slate-500 border-slate-300 hover:border-teal-300'">
                            {{ f.label }} <span class="opacity-70">({{ logFilterCount(f.key) }})</span>
                        </button>
                        <button type="button" @click="newestFirst = !newestFirst"
                                class="ml-1 px-2 py-0.5 rounded-full text-2xs font-semibold border border-slate-300 bg-white text-slate-500 hover:border-teal-300"
                                title="Inverser l'ordre">
                            {{ newestFirst ? '↑ Plus récent d\'abord' : '↓ Chronologique' }}
                        </button>
                    </div>
                </div>

                <div class="space-y-1">
                    <div v-for="ev in filteredLogEvents" :key="ev.index"
                         class="flex items-start gap-2 px-2 py-1.5 rounded-lg text-[11px]"
                         :class="[playLogRowClass(ev), diceBreakdown(ev) ? 'cursor-pointer hover:ring-1 hover:ring-teal-200' : '']"
                         :title="diceBreakdown(ev) ? 'Voir le détail des dés' : undefined"
                         @click="toggleEvent(ev)">
                        <span class="shrink-0 font-mono text-3xs px-1 py-0.5 rounded bg-white/70 text-slate-500">
                            T{{ String(ev.turn).padStart(2, '0') }}
                        </span>
                        <span v-if="ev.team" class="w-2 h-2 rounded-full mt-1 shrink-0"
                              :class="ev.team === 'internal' ? 'bg-blue-400' : 'bg-orange-400'"></span>
                        <span class="shrink-0">{{ playLogIcon(ev) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-700 flex items-center gap-1.5 flex-wrap">
                                <span>{{ ev.text }}</span>
                                <span v-if="ev.score"
                                      class="px-1.5 py-0.5 rounded bg-slate-700 text-white text-2xs font-black tabular-nums">
                                    {{ ev.score }}
                                </span>
                            </div>

                            <!-- Duel : acteurs structurés (logs récents) -->
                            <div v-if="ev.attacker || ev.defender" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                <span v-if="ev.attacker"
                                      class="inline-flex items-center gap-1 pl-0.5 pr-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-2xs font-bold">
                                    <span class="w-4 h-4 rounded-full overflow-hidden bg-emerald-200 shrink-0">
                                        <img loading="lazy" decoding="async" v-if="eventPlayerPhoto(ev.attacker)" :src="eventPlayerPhoto(ev.attacker)" class="w-full h-full object-cover" alt=""/>
                                    </span>
                                    N°{{ ev.attacker.number ?? '?' }} {{ ev.attacker.name }}
                                    <span v-if="eventActionLabel(ev.action)" class="opacity-60 font-semibold">· {{ eventActionLabel(ev.action) }}</span>
                                </span>
                                <span v-if="ev.attacker && ev.defender" class="text-2xs text-slate-400">⚔️</span>
                                <span v-if="ev.defender"
                                      class="inline-flex items-center gap-1 pl-0.5 pr-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 text-2xs font-bold">
                                    <span class="w-4 h-4 rounded-full overflow-hidden bg-rose-200 shrink-0">
                                        <img loading="lazy" decoding="async" v-if="eventPlayerPhoto(ev.defender)" :src="eventPlayerPhoto(ev.defender)" class="w-full h-full object-cover" alt=""/>
                                    </span>
                                    N°{{ ev.defender.number ?? '?' }} {{ ev.defender.name }}
                                    <span v-if="eventActionLabel(ev.def_action)" class="opacity-60 font-semibold">· {{ eventActionLabel(ev.def_action) }}</span>
                                </span>

                                <!-- Mini-indicateur de zone (5 segments, sens d'attaque) -->
                                <span v-if="Number.isFinite(ev.zone)" class="inline-flex items-center gap-0.5 ml-auto"
                                      :title="`Zone ${ev.zone + 1}/5 — ${ev.team === 'internal' ? 'attaque →' : '← attaque'}`">
                                    <span v-for="z in 5" :key="z"
                                          class="w-2 h-1 rounded-sm"
                                          :class="(ev.team === 'internal' ? z - 1 : 5 - z) === ev.zone
                                              ? (ev.team === 'internal' ? 'bg-blue-500' : 'bg-orange-500')
                                              : 'bg-slate-200'"></span>
                                </span>
                            </div>

                            <!-- Fallback anciens matchs : détails texte -->
                            <div v-else-if="ev.details?.length" class="text-2xs text-slate-400">
                                {{ ev.details.join(' · ') }}
                            </div>

                            <!-- Détail des dés (déplié au clic) -->
                            <div v-if="expandedEvent === ev.index && diceBreakdown(ev)"
                                 class="mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-2xs">
                                <div class="rounded-md bg-emerald-50 border border-emerald-100 px-2 py-1">
                                    <div class="font-bold text-emerald-700">Attaque {{ diceBreakdown(ev).attack.total }}</div>
                                    <div class="text-emerald-700/80">base {{ diceBreakdown(ev).attack.base }}</div>
                                    <div v-for="(a, i) in diceBreakdown(ev).attack.additions" :key="i" class="text-emerald-700/80">{{ a }}</div>
                                    <div v-if="diceBreakdown(ev).attack.tired" class="text-rose-500">{{ diceBreakdown(ev).attack.tired }}</div>
                                </div>
                                <div class="rounded-md bg-rose-50 border border-rose-100 px-2 py-1">
                                    <div class="font-bold text-rose-700">Défense {{ diceBreakdown(ev).defense.total }}</div>
                                    <div class="text-rose-700/80">base {{ diceBreakdown(ev).defense.base }}</div>
                                    <div v-for="(a, i) in diceBreakdown(ev).defense.additions" :key="i" class="text-rose-700/80">{{ a }}</div>
                                    <div v-if="diceBreakdown(ev).defense.tired" class="text-rose-500">{{ diceBreakdown(ev).defense.tired }}</div>
                                </div>
                                <div v-if="diceBreakdown(ev).winner" class="sm:col-span-2 text-slate-500">
                                    → Avantage {{ diceBreakdown(ev).winner }} de {{ diceBreakdown(ev).diff }}
                                    <template v-if="diceBreakdown(ev).crit"> · critique ({{ diceBreakdown(ev).crit }})</template>
                                    <template v-if="diceBreakdown(ev).reroll"> · relance du capitaine</template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!filteredLogEvents.length" class="text-[11px] text-slate-500 italic text-center py-4">
                        Aucune action de ce type dans ce match.
                    </p>
                </div>
            </div>
        </div>

        <!-- Chargement / aucun match -->
        <div v-else class="min-h-[300px] flex items-center justify-center rounded-xl border border-dashed border-slate-200 text-slate-400 text-sm">
            <div v-if="loadingMatchId" class="text-center p-8">
                <PixelLoader class="mx-auto mb-3" size="lg" />
                <p>Chargement du match…</p>
            </div>
            <div v-else class="text-center p-8">
                <div class="text-3xl mb-2">🏟️</div>
                <p>Aucun match joué pour l'instant.</p>
            </div>
        </div>
    </div>
    <Modal :show="!!selectedPlayer" title="Fiche du joueur" :subtitle="selectedPlayer?.teamName" max-width="lg" @close="selectedPlayer = null">
        <PlayerDetailContent v-if="selectedPlayer" :player="selectedPlayer" />
    </Modal>
</template>
