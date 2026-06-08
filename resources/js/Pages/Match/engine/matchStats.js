// resources/js/Pages/Match/engine/matchStats.js
// Construction des statistiques de fin de match (match_stats envoyées au backend).
// Module pur : ne dépend d'aucun état du moteur, uniquement des events fournis.

export function emptyPlayerMatchStats() {
    return {
        offense: {
            pass:    { attempts: 0, success: 0 },
            shot:    { attempts: 0, success: 0 },
            dribble: { attempts: 0, success: 0 },
            special: { attempts: 0, success: 0 },
            goals:   0,
        },
        defense: {
            intercept: { attempts: 0, success: 0 },
            tackle:    { attempts: 0, success: 0 },
            block:     { attempts: 0, success: 0 },
            hands:     { attempts: 0, success: 0 },
            punch:     { attempts: 0, success: 0 },
            gkSpecial: { attempts: 0, success: 0 },
        },
        duelsWon: 0, duelsLost: 0,
    };
}

export function buildMatchStats(events, goalEvents = []) {
    const out = {
        players: {},
        teams: {
            home: { goals: 0, shots: 0, passes: 0, dribbles: 0, duelsWon: 0, duelsLost: 0 },
            away: { goals: 0, shots: 0, passes: 0, dribbles: 0, duelsWon: 0, duelsLost: 0 },
        },
    };

    // Pour résoudre l'équipe d'un buteur depuis son first action event
    const playerTeamCache = {};

    for (const ev of events) {
        if (ev.attack?.game_player_id) {
            const pid = ev.attack.game_player_id;
            if (!out.players[pid]) out.players[pid] = emptyPlayerMatchStats();
            if (!playerTeamCache[pid]) playerTeamCache[pid] = ev.attack.team;

            const act = ev.attack.action;
            if (act === "pass")    out.players[pid].offense.pass.attempts++;
            if (act === "shot")    out.players[pid].offense.shot.attempts++;
            if (act === "dribble") out.players[pid].offense.dribble.attempts++;
            if (act === "special") out.players[pid].offense.special.attempts++;

            const t = ev.attack.team === "internal" ? "home" : "away";

            if (act === "pass")    out.teams[t].passes++;
            if (act === "dribble") out.teams[t].dribbles++;

            if (ev.result === "attack") {
                if (act === "pass")    out.players[pid].offense.pass.success++;
                if (act === "dribble") out.players[pid].offense.dribble.success++;

                if (act === "shot" || act === "special") {
                    out.players[pid].offense.shot.success++;
                    out.teams[t].shots++;
                }
                // ⚠️ Les buts ne sont PLUS comptés ici — voir étape 2 ci-dessous
                out.players[pid].duelsWon++;
                out.teams[t].duelsWon++;
            } else if (ev.result === "defense") {
                out.players[pid].duelsLost++;
                out.teams[t].duelsLost++;
                if (act === "shot" || act === "special") out.teams[t].shots++;
            }
        }

        if (ev.defense?.game_player_id) {
            const pid = ev.defense.game_player_id;
            if (!out.players[pid]) out.players[pid] = emptyPlayerMatchStats();
            if (!playerTeamCache[pid]) playerTeamCache[pid] = ev.defense.team;

            const def = ev.defense.action;
            if (def === "intercept")  out.players[pid].defense.intercept.attempts++;
            if (def === "tackle")     out.players[pid].defense.tackle.attempts++;
            if (def === "block")      out.players[pid].defense.block.attempts++;
            if (def === "hands")      out.players[pid].defense.hands.attempts++;
            if (def === "punch")      out.players[pid].defense.punch.attempts++;
            if (def === "gk-special") out.players[pid].defense.gkSpecial.attempts++;

            if (ev.result === "defense") {
                if (def === "intercept")  out.players[pid].defense.intercept.success++;
                if (def === "tackle")     out.players[pid].defense.tackle.success++;
                if (def === "block")      out.players[pid].defense.block.success++;
                if (def === "hands")      out.players[pid].defense.hands.success++;
                if (def === "punch")      out.players[pid].defense.punch.success++;
                if (def === "gk-special") out.players[pid].defense.gkSpecial.success++;
                out.players[pid].duelsWon++;
            } else if (ev.result === "attack") {
                out.players[pid].duelsLost++;
            }
        }
    }

    // ── Étape 2 : compter les buts depuis goalEvents (source de vérité) ──
    for (const gev of goalEvents) {
        const pid = gev.player_id;
        if (!pid) continue;
        if (!out.players[pid]) out.players[pid] = emptyPlayerMatchStats();
        out.players[pid].offense.goals = (out.players[pid].offense.goals ?? 0) + 1;

        const team = playerTeamCache[pid];
        if (team) {
            const t = team === "internal" ? "home" : "away";
            out.teams[t].goals++;
        }
    }

    return out;
}
