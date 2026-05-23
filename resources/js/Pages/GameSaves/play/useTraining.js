// resources/js/Pages/GameSaves/play/useTraining.js
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useTraining({ gameSave, season, week }) {

    const MAX_TRAININGS_PER_WEEK = 3;

    const trainingState = computed(() => gameSave.value.state?.training ?? null);

    const remainingTrainingsThisWeek = computed(() => {
        const s = trainingState.value;
        if (!s) return MAX_TRAININGS_PER_WEEK;
        if (s.season !== season.value || s.week !== week.value) return MAX_TRAININGS_PER_WEEK;
        const used = Array.isArray(s.entries) ? s.entries.length : 0;
        return Math.max(0, MAX_TRAININGS_PER_WEEK - used);
    });

    const hasPlayerBeenTrainedThisWeek = (playerId) => {
        const s = trainingState.value;
        if (!s) return false;
        if (s.season !== season.value || s.week !== week.value) return false;
        if (!Array.isArray(s.entries)) return false;
        return s.entries.some(e => Number(e.player_id) === Number(playerId));
    };

    const availableTrainingStats = [
        { key: 'shot',       label: 'Tir'           },
        { key: 'pass',       label: 'Passe'          },
        { key: 'dribble',    label: 'Dribble'        },
        { key: 'attack',     label: 'Attaque'        },
        { key: 'defense',    label: 'Défense'        },
        { key: 'speed',      label: 'Vitesse'        },
        { key: 'block',      label: 'Block'          },
        { key: 'intercept',  label: 'Interception'   },
        { key: 'tackle',     label: 'Tacle'          },
        { key: 'hand_save',  label: 'Arrêt main'     },
        { key: 'punch_save', label: 'Arrêt poings'   },
    ];

    const selectedTrainings = ref([]);

    const addTrainingSlot = () => {
        if (selectedTrainings.value.length >= MAX_TRAININGS_PER_WEEK) return;
        selectedTrainings.value.push({ player_id: null, stat: 'shot' });
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
    const aiTrainingEntries = computed(() => {
        const s = trainingState.value;
        if (!s) return [];
        if (Number(s.season) !== Number(season.value) || Number(s.week) !== Number(week.value)) return [];
        return Array.isArray(s.ai_entries) ? s.ai_entries : [];
    });

    return {
        trainingState,
        remainingTrainingsThisWeek,
        hasPlayerBeenTrainedThisWeek,
        availableTrainingStats,
        selectedTrainings,
        addTrainingSlot, removeTrainingSlot,
        canSubmitTraining, submitTraining,
        aiTrainingEntries,
    };
}
