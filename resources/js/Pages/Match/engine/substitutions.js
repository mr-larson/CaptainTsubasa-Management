// resources/js/Pages/Match/engine/substitutions.js
// Remplacements manuels (joueur humain) et automatiques (IA, sur fatigue).

let _state  = null;
let _roster = null;
let _rootEl = null;

let _getPlayerId    = null;
let _updateStaminaUI= null;
let _updateTeamCard = null;
let _pushLogEntry   = null;
let _refreshUI      = null;
let _isAITeam       = null;

export function initSubstitutionsModule({
    state, roster, rootEl,
    getPlayerId, updateStaminaUI, updateTeamCard, pushLogEntry, refreshUI, isAITeam,
}) {
    _state           = state;
    _roster          = roster;
    _rootEl          = rootEl;
    _getPlayerId     = getPlayerId;
    _updateStaminaUI = updateStaminaUI;
    _updateTeamCard  = updateTeamCard;
    _pushLogEntry    = pushLogEntry;
    _refreshUI       = refreshUI;
    _isAITeam        = isAITeam;
}

function isPlayerSentOff(team, slot) {
    const dbId = _roster.getPlayerInfo(team, slot)?.id ?? null;
    if (!dbId) return false;

    const isRedCarded = _state.foulEvents.some(
        e => e.type === 'card' && e.card_type === 'red' && e.player_id === dbId
    );
    const isDoubleYellow = _state.foulEvents.filter(
        e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === dbId
    ).length >= 2;

    return isRedCarded || isDoubleYellow;
}

/**
 * Effectue un remplacement (slot sortant ↔ slot entrant) pour une équipe.
 * Retourne true si le remplacement a eu lieu.
 */
export function performSubstitution(team, outSlot, inSlot) {
    if (_state.substitutionCount >= _state.MAX_SUBSTITUTIONS) return false;
    if (outSlot === inSlot) return false;

    const outId = _getPlayerId(team, outSlot);
    const outEl = _rootEl.querySelector(`[data-player="${outId}"]`);
    if (!outEl) return false;

    if (isPlayerSentOff(team, outSlot)) return false;

    const inId = _getPlayerId(team, inSlot);
    const inEl = _rootEl.querySelector(`[data-player="${inId}"]`);

    if (inEl) {
        if (inEl.classList.contains('unavailable')) return false;
        inEl.style.left = outEl.style.left;
        inEl.style.top  = outEl.style.top;
        if (outEl.dataset.zone) inEl.dataset.zone = outEl.dataset.zone;
        outEl.classList.add('unavailable');
    } else {
        const inInfo = _roster.getPlayerInfo(team, inSlot);
        if (!inInfo) return false;

        _roster.rosters[team].set(outSlot, { ...inInfo, isStarter: true });
        _roster.rosters[team].set(inSlot, { ...inInfo, isAvailable: false });

        const subNumber = 11 + _state.substitutionCount + 1;
        outEl.textContent       = String(subNumber);
        outEl.dataset.jersey    = String(subNumber);
        outEl.dataset.firstname = inInfo.firstname;
        outEl.dataset.lastname  = inInfo.lastname;
        outEl.dataset.position  = inInfo.position;

        const inStamina = inInfo.stats?.stamina ?? 80;
        _state.stamina[outId]    = inStamina;
        _state.staminaMax[outId] = inStamina;
        outEl.classList.remove('unavailable');
        _updateStaminaUI(outId, _state.ball, () => _updateTeamCard(_state.ball));
    }

    if (_state.ball.team === team && _state.ball.number === outSlot) {
        _state._moveBallFn(team, outSlot);
    }

    const outInfo = _roster.getPlayerInfo(team, outSlot);
    const inInfo  = _roster.getPlayerInfo(team, inSlot);

    _state.substitutions.push({
        team, outSlot, inSlot,
        turn:        _state.turns,
        outPlayerId: outInfo?.id ?? null,
        inPlayerId:  inInfo?.id  ?? null,
    });
    _state.substitutionCount++;

    _pushLogEntry('substitutionTitle', [
        `🔄 ${outInfo?.lastname ?? '#' + outSlot} → ${inInfo?.lastname ?? '#' + inSlot}`,
    ], null, _state);

    _refreshUI();
    return true;
}

/**
 * IA : remplace automatiquement un joueur fatigué (< 50% d'endurance) par
 * le meilleur remplaçant disponible au même poste.
 */
export function aiCheckSubstitutions() {
    if (_state.isGameOver) return;
    if (_state.substitutionCount >= _state.MAX_SUBSTITUTIONS) return;

    for (const aiTeam of ['internal', 'external']) {
        if (!_isAITeam(aiTeam)) continue;

        for (let outSlot = 1; outSlot <= 11; outSlot++) {
            const outId = _getPlayerId(aiTeam, outSlot);
            const outEl = _rootEl.querySelector(`[data-player="${outId}"]`);
            if (!outEl || outEl.classList.contains('unavailable')) continue;
            if (isPlayerSentOff(aiTeam, outSlot)) continue;

            const stMax = _state.staminaMax[outId] ?? 100;
            const stCur = _state.stamina[outId]    ?? stMax;
            const ratio = stMax > 0 ? stCur / stMax : 1;
            if (ratio >= 0.5) continue;

            const outInfo = _roster.getPlayerInfo(aiTeam, outSlot);
            const outPos  = outInfo?.position ?? '';

            let bestInSlot = null;
            let bestRatio  = 0;

            const subsPool = _roster.getSubs(aiTeam);
            for (const { slot: inSlot, info: inInfo } of subsPool) {
                if (inInfo.isAvailable === false) continue;
                if (_state.substitutions.some(s => s.inSlot === inSlot && s.team === aiTeam)) continue;

                const inStMax = _state.staminaMax[_getPlayerId(aiTeam, inSlot)] ?? 100;
                const inStCur = _state.stamina[_getPlayerId(aiTeam, inSlot)]    ?? inStMax;
                const inRatio = inStMax > 0 ? inStCur / inStMax : 1;

                const samePos = inInfo.position === outPos;
                if (samePos && inRatio > bestRatio) {
                    bestRatio  = inRatio;
                    bestInSlot = inSlot;
                } else if (!bestInSlot) {
                    bestInSlot = inSlot;
                }
            }

            if (bestInSlot !== null) {
                performSubstitution(aiTeam, outSlot, bestInSlot);
                break;
            }
        }
    }
}
