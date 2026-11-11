// Signal visuel local au terrain, sans son. Le callback attend la fin du signal.
export function createWhistleCue(rootEl) {
    let element = null;
    let timer = null;

    function dispose() {
        if (timer !== null) clearTimeout(timer);
        timer = null;
        element?.remove();
        element = null;
    }

    function show(kind, onComplete = () => {}) {
        dispose();
        const host = rootEl.querySelector('#field-wrapper');
        if (!host) { onComplete(); return; }
        const ending = kind === 'end';
        element = rootEl.ownerDocument.createElement('div');
        element.className = `match-whistle${ending ? ' match-whistle-end' : ''}`;
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        element.innerHTML = `
            <div class="match-whistle-badge">
                <svg viewBox="0 0 80 64" aria-hidden="true" class="match-whistle-icon">
                    <path d="M44 21 68 13l5 14-21 9a20 20 0 1 1-8-15Z" fill="currentColor"/>
                    <circle cx="31" cy="39" r="9" fill="none" stroke="white" stroke-width="4"/>
                    <path d="m53 19 4 12" fill="none" stroke="white" stroke-width="4"/>
                    <path class="match-whistle-rays" d="M13 15 8 9M28 11V3M43 12l5-7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                <span>${ending ? 'Fin du match' : 'Coup d’envoi'}</span>
            </div>`;
        host.appendChild(element);
        timer = setTimeout(() => {
            dispose();
            onComplete();
        }, ending ? 1000 : 700);
    }

    return { show, dispose };
}
