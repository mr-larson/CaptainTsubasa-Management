// Fenêtres de confirmation et d'information au style du jeu, à la place de
// window.confirm() / window.alert(). Un seul dialogue à la fois, affiché par
// <AppDialog /> (monté dans AuthenticatedLayout).
import { reactive } from 'vue';

export const dialogState = reactive({
    open:         false,
    title:        '',
    message:      '',
    details:      [],      // lignes à puces facultatives
    confirmLabel: 'Confirmer',
    cancelLabel:  'Annuler',
    danger:       false,   // bouton de confirmation en rouge (suppression…)
    alertOnly:    false,   // un seul bouton (information)
    resolve:      null,
});

function openDialog(options) {
    // Un dialogue encore ouvert est refermé comme « annulé ».
    dialogState.resolve?.(false);
    Object.assign(dialogState, {
        title: '', message: '', details: [], confirmLabel: 'Confirmer', cancelLabel: 'Annuler',
        danger: false, alertOnly: false,
        ...options,
        open: true,
    });
    return new Promise(resolve => { dialogState.resolve = resolve; });
}

/** Demande une confirmation. Renvoie une promesse : true si l'utilisateur confirme. */
export function confirmDialog(options) {
    return openDialog(typeof options === 'string' ? { message: options } : options);
}

/** Affiche une information (un seul bouton). La promesse se résout à la fermeture. */
export function alertDialog(options) {
    const opts = typeof options === 'string' ? { message: options } : options;
    return openDialog({ title: 'Information', confirmLabel: 'OK', ...opts, alertOnly: true });
}

export function closeDialog(result) {
    const resolve = dialogState.resolve;
    dialogState.open    = false;
    dialogState.resolve = null;
    resolve?.(result);
}
