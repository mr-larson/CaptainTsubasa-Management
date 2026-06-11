// resources/js/Pages/Match/engine/RosterService.js
import { STATS, POSITION_BONUS, SECONDARY_POSITION_BONUS_FACTOR, OFF_POSITION_MALUS, MORALE_FACTORS, MORALE_DEFAULT } from './constants.js';
import { FORMATIONS, DEFAULT_FORMATION } from './formations.js';

export class RosterService {
    constructor({ rosters, statCoef, positionBonus, formations }) {
        this.rosters        = rosters;
        this.STAT_COEF      = statCoef;
        this.POSITION_BONUS = positionBonus;
        this.formations     = formations ?? {};
    }

    static create(matchConfig, { statCoef, positionBonus }) {
        const rosters = { internal: new Map(), external: new Map() };

        const normalizePlayers = (list = []) => (Array.isArray(list) ? list : []);

        const resolvePhoto = (p) =>
            p?.photo_url   ?? p?.photo      ?? p?.image_url  ??
            p?.avatar_url  ?? p?.photo_path ?? p?.portrait_url ??
            p?.portrait    ?? p?.picture_url ?? p?.picture   ?? null;

        const resolveStats = (p) => {
            const s = p?.stats ?? null;
            const src = s ?? p ?? {};
            return {
                shot:       src.shot       ?? 0,
                pass:       src.pass       ?? 0,
                dribble:    src.dribble    ?? 0,
                tackle:     src.tackle     ?? 0,
                intercept:  src.intercept  ?? 0,
                block:      src.block      ?? 0,
                attack:     src.attack     ?? 0,
                defense:    src.defense    ?? 0,
                speed:      src.speed      ?? 0,
                stamina:    src.stamina    ?? 0,
                hand_save:  src.hand_save  ?? 0,
                punch_save: src.punch_save ?? 0,
                heading:    src.heading    ?? 0,
            };
        };

        const seedTeam = (teamKey) => {
            const playersRaw = normalizePlayers(matchConfig.teams?.[teamKey]?.players);

            // Séparer titulaires et remplaçants
            let starters = playersRaw.filter(p => p && p.is_starter === true);
            if (!starters.length) starters = playersRaw.slice(0, 11);

            const subs = playersRaw.filter(p => p && p.is_starter === false);

            // Slots 1-11 = titulaires sur le terrain
            for (let slot = 1; slot <= 11; slot++) {
                const p = starters[slot - 1] ?? null;
                rosters[teamKey].set(slot, p ? {
                    id:           p.id        ?? null,
                    number:       p.number    ?? slot,
                    firstname:    p.firstname ?? "",
                    lastname:     p.lastname  ?? "",
                    position:     p.position  ?? "",
                    secondaryPositions: Array.isArray(p.secondary_positions) ? p.secondary_positions : [],
                    photo:        resolvePhoto(p),
                    stats:        resolveStats(p),
                    specialMoves: Array.isArray(p.special_moves) ? p.special_moves : [],
                    isAvailable:  p.is_available !== false,
                    yellowCards:  p.yellow_cards ?? 0,
                    morale:       p.morale ?? MORALE_DEFAULT,
                    isStarter:    true,
                    isCaptain:    p.is_captain ?? false,
                    contractId:   p.contract_id ?? null,
                    captainRerollsRemaining: p.captain_rerolls_remaining ?? 3,
                } : {
                    id: null, number: slot,
                    firstname: "Joueur", lastname: `#${slot}`,
                    position: "", secondaryPositions: [], photo: null, stats: null, specialMoves: [],
                    isAvailable: true, yellowCards: 0, morale: MORALE_DEFAULT, isStarter: true,
                    isCaptain:    false,   // ← pas de p ici
                    contractId:   null,
                    captainRerollsRemaining: 3,
                });
            }

            // Slots 12+ = remplaçants (pas sur le terrain)
            for (let i = 0; i < subs.length; i++) {
                const p    = subs[i];
                const slot = 12 + i;
                rosters[teamKey].set(slot, {
                    id:           p.id        ?? null,
                    number:       p.number    ?? slot,
                    firstname:    p.firstname ?? "",
                    lastname:     p.lastname  ?? "",
                    position:     p.position  ?? "",
                    secondaryPositions: Array.isArray(p.secondary_positions) ? p.secondary_positions : [],
                    photo:        resolvePhoto(p),
                    stats:        resolveStats(p),
                    specialMoves: Array.isArray(p.special_moves) ? p.special_moves : [],
                    isAvailable:  p.is_available !== false,
                    yellowCards:  p.yellow_cards ?? 0,
                    morale:       p.morale ?? MORALE_DEFAULT,
                    isStarter:    false,
                });
            }
        };

        seedTeam("internal");
        seedTeam("external");

        const formations = {
            internal: matchConfig.teams?.internal?.formation ?? DEFAULT_FORMATION,
            external: matchConfig.teams?.external?.formation ?? DEFAULT_FORMATION,
        };

        return new RosterService({ rosters, statCoef, positionBonus, formations });
    }

    // -----------------------------------------------------------
    //   Accès données
    // -----------------------------------------------------------
    getPlayerInfo(team, slotNumber) {
        return this.rosters[team]?.get(slotNumber) ?? null;
    }

    // Retourne tous les remplaçants (slots 12+) d'une équipe
    getSubs(team) {
        const result = [];
        const map = this.rosters[team];
        if (!map) return result;
        for (const [slot, info] of map.entries()) {
            if (slot >= 12 && info && !info.isStarter) {
                result.push({ slot, info });
            }
        }
        return result;
    }

    clampStat(v) {
        const n = Number(v ?? 0);
        return Number.isFinite(n) ? Math.max(0, n) : 0;
    }

    getStat(team, slotNumber, key) {
        const info  = this.getPlayerInfo(team, slotNumber);
        const stats = info?.stats ?? {};
        return this.clampStat(stats[key]);
    }

    getRoleFromPositionString(pos) {
        const p = String(pos || "").toLowerCase();
        if (p.includes("goalkeeper") || p === "gk") return "GK";
        if (p.includes("def")        || p === "df") return "DF";
        if (p.includes("mid")        || p === "mf") return "MF";
        if (p.includes("for") || p.includes("att") || p === "fw") return "FW";
        return null;
    }

    getSpecialMoves(team, slotNumber) {
        const info  = this.getPlayerInfo(team, slotNumber);
        const moves = info?.specialMoves;
        return Array.isArray(moves) ? moves : [];
    }

    getPlayerRole(team, slotNumber) {
        const info = this.getPlayerInfo(team, slotNumber);
        return this.getRoleFromPositionString(info?.position);
    }

    /** Rôle (GK/DF/MF/FW) du slot occupé, dérivé de la formation de l'équipe. */
    getSlotRole(team, slotNumber) {
        const formKey   = this.formations?.[team] ?? DEFAULT_FORMATION;
        const formation = FORMATIONS[formKey] ?? FORMATIONS[DEFAULT_FORMATION];
        const zone      = formation.slots?.[slotNumber]?.zone;

        if (zone === 0) return "GK";
        if (zone === 1) return "DF";
        if (zone === 2 || zone === 3) return "MF";
        if (zone === 4) return "FW";

        // Slot hors formation (remplaçants 12+) : poste naturel
        return this.getPlayerRole(team, slotNumber);
    }

    /**
     * Maîtrise du poste occupé :
     * - "primary"   : le slot correspond au poste naturel
     * - "secondary" : le slot correspond à un poste secondaire
     * - "off"       : joueur hors poste
     */
    positionMastery(team, slotNumber) {
        const slotRole    = this.getSlotRole(team, slotNumber);
        const naturalRole = this.getPlayerRole(team, slotNumber);

        // Données manquantes (joueur fictif, position inconnue) → neutre
        if (!slotRole || !naturalRole) return { role: slotRole, level: "primary" };

        if (slotRole === naturalRole) return { role: slotRole, level: "primary" };

        const info      = this.getPlayerInfo(team, slotNumber);
        const secondary = (info?.secondaryPositions ?? [])
            .map(p => this.getRoleFromPositionString(p));

        if (secondary.includes(slotRole)) return { role: slotRole, level: "secondary" };

        return { role: slotRole, level: "off" };
    }

    /** Facteur de moral appliqué aux bases de duel (miroir de MoraleService::matchFactor). */
    moraleFactor(team, slotNumber) {
        const info   = this.getPlayerInfo(team, slotNumber);
        const morale = Number(info?.morale ?? MORALE_DEFAULT);
        const tier   = MORALE_FACTORS.find(t => morale <= t.max);
        return tier ? tier.factor : 1.0;
    }

    positionBonusMultiplier(team, slotNumber, tag) {
        const { role, level } = this.positionMastery(team, slotNumber);

        if (level === "off") return 1.0 - OFF_POSITION_MALUS;

        if (!role) return 1.0;
        const r = this.POSITION_BONUS[role];
        if (!r)  return 1.0;
        const b = Number(r[tag] ?? 0);
        if (!Number.isFinite(b)) return 1.0;

        const factor = level === "secondary" ? SECONDARY_POSITION_BONUS_FACTOR : 1.0;
        return 1.0 + b * factor;
    }

    globalAttackMultiplier(team, slotNumber) {
        const attackStat = this.getStat(team, slotNumber, "attack");
        return 1.0 + (attackStat / 100) * 0.1;
    }

    globalDefenseMultiplier(team, slotNumber) {
        const defenseStat = this.getStat(team, slotNumber, "defense");
        return 1.0 + (defenseStat / 100) * 0.1;
    }

    speedStaminaCostReduction(team, slotNumber) {
        const speedStat = this.getStat(team, slotNumber, "speed");
        return 1.0 - (speedStat / 100) * 0.1;
    }

    // -----------------------------------------------------------
    //   Bases de calcul duel
    // -----------------------------------------------------------
    attackBaseFor(actionKey, team, slotNumber) {
        const base = STATS.attack[actionKey]?.power ?? 10;
        const map  = {
            pass:    { stat: "pass",    bonus: "pass"    },
            dribble: { stat: "dribble", bonus: "dribble" },
            shot:    { stat: "shot",    bonus: "shot"    },
            special: { stat: "attack",  bonus: "attack"  },
        };
        if (actionKey === "cross" || actionKey === "long_pass") {
            const passStat   = this.getStat(team, slotNumber, "pass");
            const attackStat = this.getStat(team, slotNumber, "attack");
            const combined   = passStat * 0.7 + attackStat * 0.3;
            let raw = base + combined * this.STAT_COEF;
            raw *= this.positionBonusMultiplier(team, slotNumber, "pass");
            raw *= this.globalAttackMultiplier(team, slotNumber);
            raw *= this.moraleFactor(team, slotNumber);
            return raw;
        }

        const m = map[actionKey] ?? null;
        let raw = base;
        if (m) raw = base + this.getStat(team, slotNumber, m.stat) * this.STAT_COEF;
        if (m) raw *= this.positionBonusMultiplier(team, slotNumber, m.bonus);
        raw *= this.globalAttackMultiplier(team, slotNumber);
        raw *= this.moraleFactor(team, slotNumber);
        return raw;
    }

    defenseBaseFor(defenseAction, defenseTeam, defenseSlotNumber, isKeeper = false) {
        const baseField = STATS.defenseField[defenseAction]?.power;
        const baseGk    = STATS.defenseGK[defenseAction]?.power;
        const base      = baseField ?? baseGk ?? 10;

        if (isKeeper) {
            const mapGK   = { hands: "hand_save", punch: "punch_save", "gk-special": "defense" };
            const statKey = mapGK[defenseAction] ?? null;
            let raw = base + (statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) * this.STAT_COEF : 0);
            raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, "gk");
            raw *= this.globalDefenseMultiplier(defenseTeam, defenseSlotNumber);
            raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
            return raw;
        }

        if (defenseAction === "heading") {
            const headingStat = this.getStat(defenseTeam, defenseSlotNumber, "heading");
            const speedStat   = this.getStat(defenseTeam, defenseSlotNumber, "speed");
            const combined    = headingStat * 0.8 + speedStat * 0.2;
            let raw = base + combined * this.STAT_COEF;
            raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, "defend");
            raw *= this.globalDefenseMultiplier(defenseTeam, defenseSlotNumber);
            raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
            return raw;
        }

        const mapField = { block: "block", intercept: "intercept", tackle: "tackle", "field-special": "defense" };
        const statKey  = mapField[defenseAction] ?? null;
        let raw = base + (statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) * this.STAT_COEF : 0);
        const bonusTag = (defenseAction === "block") ? "block" : "defend";
        raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, bonusTag);
        raw *= this.globalDefenseMultiplier(defenseTeam, defenseSlotNumber);
        raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
        return raw;
    }
}

// -----------------------------------------------------------
//   Helper special moves (hors classe, utilisé par resolvers)
// -----------------------------------------------------------
export function specialBaseFor(move, team, slotNumber, roster) {
    if (!move) return 0;
    const info       = roster.getPlayerInfo(team, slotNumber);
    const stats      = info?.stats ?? {};
    const baseAction = String(move.base_action || "").trim();
    const mode       = String(move.mode || "attack").toLowerCase();
    const actionStat = Number(stats[baseAction] ?? 0) || 0;
    const globalKey  = (mode === "defense") ? "defense" : "attack";
    const globalStat = Number(stats[globalKey] ?? 0) || 0;
    return ((actionStat + globalStat) / 2) * roster.moraleFactor(team, slotNumber);
}
