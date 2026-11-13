import { onMounted, ref } from 'vue';
import { confirmDialog } from '@/Composables/useDialog.js';

// Copie profonde : le formulaire ne doit jamais modifier les props (sinon une
// saisie non enregistrée réapparaît comme sauvegardée en revenant sur l'élément).
export const clone = (value) => (value == null ? value : JSON.parse(JSON.stringify(value)));

/**
 * Logique commune des écrans liste + fiche (joueurs / équipes, en partie ou en base) :
 * sélection d'un élément, création sur la même page avec fiche vide, et
 * confirmation avant d'abandonner une saisie en cours.
 *
 * @param {object}   options
 * @param {object}   options.form       formulaire Inertia (useForm)
 * @param {Function} options.items      () => liste courante (props)
 * @param {object}   options.blank      valeurs d'une fiche vide
 * @param {Function} options.apply      (item) => remplit le formulaire avec l'élément
 * @param {Function} [options.onCreate] appelé à l'ouverture d'une fiche vide
 * @param {Function} [options.initial]  () => { selectedId, startCreate } lus à l'ouverture
 * @param {string}   [options.entityLabel] « le nouveau joueur », « la nouvelle équipe »…
 */
export function useEntityEditor({ form, items, blank, apply, onCreate, initial, entityLabel = "L'élément" }) {
    const creating = ref(false);
    const selectedId = ref(null);

    const draftKeys = Object.keys(blank).filter((k) => k !== 'id');
    const hasDraft = () =>
        draftKeys.some((k) => JSON.stringify(form[k] ?? null) !== JSON.stringify(blank[k] ?? null));

    function applyItem(item) {
        creating.value = false;
        selectedId.value = item.id;
        apply(item);
        form.defaults();
        form.clearErrors();
    }

    async function select(item) {
        if (creating.value && hasDraft()) {
            const ok = await confirmDialog({
                title: 'Abandonner la création',
                message: `${entityLabel} n'est pas encore créé(e). Abandonner la saisie ?`,
                confirmLabel: 'Abandonner',
                danger: true,
            });
            if (!ok) return;
        }
        applyItem(item);
    }

    function startCreating() {
        if (creating.value) return;
        Object.assign(form, clone(blank));
        form.defaults();
        form.clearErrors();
        selectedId.value = null;
        creating.value = true;
        onCreate?.();
    }

    // Après une mise à jour / création : recharger l'élément enregistré.
    function reload(id) {
        const fresh = items().find((i) => i.id === id);
        if (fresh) applyItem(fresh);
    }

    // Après une suppression : premier élément restant, ou fiche vide.
    function afterDelete() {
        const list = items();
        if (list.length > 0) applyItem(list[0]);
        else startCreating();
    }

    onMounted(() => {
        const { selectedId: wanted = null, startCreate = false } = initial?.() ?? {};
        if (startCreate) return startCreating();
        const first = items().find((i) => i.id === wanted) ?? items()[0];
        if (first) applyItem(first);
        else startCreating();
    });

    return { creating, selectedId, select, startCreating, reload, afterDelete, applyItem };
}
