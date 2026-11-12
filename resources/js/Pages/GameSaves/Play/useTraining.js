// resources/js/Pages/GameSaves/Play/useTraining.js
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

export function useTraining({ gameSave, season, week, teamId, maxPerWeek, aiInitial }) {

    // Quota de la save (réglable dans Gestion), 3 par défaut.
    const MAX_TRAININGS_PER_WEEK_DEFAULT = 3;
    const maxTrainingsPerWeek = computed(() => Number(maxPerWeek?.value) || MAX_TRAININGS_PER_WEEK_DEFAULT);

    const trainingState = computed(() => gameSave.value.state?.training ?? null);

    // Séances de la semaine de l'équipe active (quota par équipe en hot-seat).
    // Les entrées sans team_id (anciennes) comptent pour toutes les équipes, comme côté serveur.
    const myEntriesThisWeek = computed(() => {
        const s = trainingState.value;
        if (!s || s.season !== season.value || s.week !== week.value) return [];
        if (!Array.isArray(s.entries)) return [];
        return s.entries.filter(e => e.team_id == null || Number(e.team_id) === Number(teamId?.value));
    });

    const remainingTrainingsThisWeek = computed(() =>
        Math.max(0, maxTrainingsPerWeek.value - myEntriesThisWeek.value.length)
    );

    const hasPlayerBeenTrainedThisWeek = (playerId) =>
        myEntriesThisWeek.value.some(e => Number(e.player_id) === Number(playerId));

    const availableTrainingStats = [
        { key: 'shot',       label: 'Tir'           },
        { key: 'pass',       label: 'Passe'          },
        { key: 'dribble',    label: 'Dribble'        },
        { key: 'attack',     label: 'Attaque'        },
        { key: 'defense',    label: 'Défense'        },
        { key: 'speed',      label: 'Vitesse'        },
        { key: 'block',      label: 'Contre'         },
        { key: 'intercept',  label: 'Interception'   },
        { key: 'tackle',     label: 'Tacle'          },
        { key: 'hand_save',  label: 'Arrêt main'     },
        { key: 'punch_save', label: 'Arrêt poings'   },
    ];

    const selectedTrainings = ref([]);

    const addTrainingSlot = (playerId = null) => {
        if (selectedTrainings.value.length >= remainingTrainingsThisWeek.value) return;
        if (remainingTrainingsThisWeek.value <= 0) return;

        if (playerId && selectedTrainings.value.some(s => Number(s.player_id) === Number(playerId))) {
            return;
        }

        selectedTrainings.value.push({
            player_id: playerId,
            stat:      availableTrainingStats[0]?.key ?? null,  // ← sans .value
        });
    };

    const removeTrainingSlot = (index) => {
        selectedTrainings.value.splice(index, 1);
    };

    const canSubmitTraining = computed(() => {
        if (remainingTrainingsThisWeek.value <= 0) return false;
        const filtered = selectedTrainings.value.filter(t => t.player_id && t.stat);
        if (!filtered.length) return false;
        const ids = filtered.map(t => t.player_id);
        return new Set(ids).size === ids.length; // joueurs distincts
    });

    const submitTraining = () => {
        const payload = selectedTrainings.value
            .filter(t => t.player_id && t.stat)
            .slice(0, remainingTrainingsThisWeek.value);
        if (!payload.length) return;

        router.post(
            route('game-saves.training.store', { gameSave: gameSave.value.id }),
            { season: season.value, week: week.value, trainings: payload },
            { preserveScroll: true, onSuccess: () => { selectedTrainings.value = []; } }
        );
    };

    // Entraînements IA sur l'équipe contrôlée cette semaine
    // Semaine affichée pour les entraînements IA
    const aiDisplayWeek = ref(null)
    const aiCurrentDisplayWeek = computed(() => aiDisplayWeek.value ?? Math.max(1, week.value - 1))
    const aiWeekMax = computed(() => week.value)

    // L'historique complet reste côté serveur : la semaine par défaut arrive avec
    // la page (aiTrainingInitial), les autres se chargent à la demande. Seules les
    // semaines closes sont mises en cache.
    const aiWeekCache = ref({})
    const aiCacheKey = (s, w) => `${s}-${w}`
    const aiTrainingEntries = computed(() => {
        const key = aiCacheKey(season.value, aiCurrentDisplayWeek.value)
        const initial = aiInitial?.value
        if (initial && aiCacheKey(initial.season, initial.week) === key) return initial.entries ?? []
        return aiWeekCache.value[key] ?? []
    })

    watch(aiCurrentDisplayWeek, async (w) => {
        const key = aiCacheKey(season.value, w)
        const initial = aiInitial?.value
        if ((w < week.value && aiWeekCache.value[key]) || (initial && aiCacheKey(initial.season, initial.week) === key)) return
        try {
            const { data } = await axios.get(route('game-saves.training.ai-week', { gameSave: gameSave.value.id, week: w }))
            // La semaine en cours n'est pas figée : on la relit à chaque affichage.
            aiWeekCache.value = { ...aiWeekCache.value, [key]: data.entries }
        } catch { /* l'onglet reste simplement vide */ }
    })

    const prevAiWeek = () => { if (aiCurrentDisplayWeek.value > 1) aiDisplayWeek.value = aiCurrentDisplayWeek.value - 1 }
    const nextAiWeek = () => { if (aiCurrentDisplayWeek.value < aiWeekMax.value) aiDisplayWeek.value = aiCurrentDisplayWeek.value + 1 }

    return {
        trainingState, myEntriesThisWeek,
        remainingTrainingsThisWeek,
        hasPlayerBeenTrainedThisWeek,
        availableTrainingStats,
        selectedTrainings,
        addTrainingSlot, removeTrainingSlot,
        canSubmitTraining, submitTraining,
        aiTrainingEntries, aiCurrentDisplayWeek, aiWeekMax, prevAiWeek, nextAiWeek,
    };
}
