// resources/js/Pages/Match/engine/RosterService.js
import { STATS, POSITION_BONUS, SECONDARY_POSITION_BONUS_FACTOR, OFF_POSITION_MALUS, MORALE_FACTORS, MORALE_DEFAULT, DUEL_CONTEXT, MAX_ZONE_INDEX } from './constants.js';
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
                luck:       src.luck       ?? 50,
            };
        };

        const seedTeam = (teamKey) => {
            const playersRaw = normalizePlayers(matchConfig.teams?.[teamKey]?.players);

            // Séparer titulaires et remplaçants
            let starters = playersRaw.filter(p => p && p.is_starter === true);
            if (!starters.length) starters = playersRaw.slice(0, 11);

            const subs = playersRaw.filter(p => p && p.is_starter === false && p.id != null);

            // Slots 1-11 = titulaires sur le terrain
            for (let slot = 1; slot <= 11; slot++) {
                // Un slot vide envoyé par le serveur (is_placeholder, id null)
                // reçoit le jeton générique « Joueur #slot ».
                const raw = starters[slot - 1] ?? null;
                const p   = raw && raw.id != null ? raw : null;
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
                    promises:     Array.isArray(p.promises) ? p.promises : [],
                    duoPartners:  Array.isArray(p.duo_partners) ? p.duo_partners : [],
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
                    duoPartners: [],
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
                    promises:     Array.isArray(p.promises) ? p.promises : [],
                    duoPartners:  Array.isArray(p.duo_partners) ? p.duo_partners : [],
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

    crossStat(team, slot) {
        return 0.7 * this.getStat(team, slot, "pass") + 0.3 * this.getStat(team, slot, "attack");
    }

    headingDuelStat(team, slot) {
        return 0.8 * this.getStat(team, slot, "heading") + 0.2 * this.getStat(team, slot, "speed");
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

    getSpecialMove(team, slotNumber, mode, baseAction = null) {
        const normalize = action => ({ hands: "hand_save", punch: "punch_save" })[action] ?? action;
        return this.getSpecialMoves(team, slotNumber)
            .find(move => move?.mode === mode && (baseAction === null || normalize(move.base_action) === normalize(baseAction)));
    }

    /**
     * Partenaire de duo (une-deux) actuellement sur le terrain dans la même
     * équipe : { slot, label, partnerInfo } ou null. Le partenaire doit être
     * titulaire (slots 1-11) et disponible.
     */
    getDuoPartnerOnField(team, slotNumber) {
        const info     = this.getPlayerInfo(team, slotNumber);
        const partners = info?.duoPartners ?? [];
        if (!info?.id || !partners.length) return null;

        const ranks = { DF: 0, MF: 1, FW: 2 };
        const rank = ranks[this.getSlotRole(team, slotNumber)];
        for (let slot = 1; slot <= 11; slot++) {
            if (slot === slotNumber) continue;
            const other = this.getPlayerInfo(team, slot);
            if (!other?.id || other.isAvailable === false) continue;
            const match = partners.find(d => d?.id === other.id);
            const partnerRank = ranks[this.getSlotRole(team, slot)];
            if (match && (!match.learned || Math.abs(rank - partnerRank) <= 1)) {
                return { slot, label: match.label ?? null, partnerInfo: other };
            }
        }
        return null;
    }

    /**
     * Base de duel du une-deux : moyenne des stats de passe des DEUX joueurs
     * du duo (recruter le bon partenaire compte), bonus de poste « pass » et
     * moral du porteur. Pas de bonus supplémentaire : l'avantage est le swap
     * de stat (passe au lieu de dribble) et de défense (intercept au lieu de
     * tackle) ; le coût d'endurance élevé fait le reste.
     */
    oneTwoBaseFor(team, slotNumber, partnerSlot) {
        const base     = STATS.attack.one_two?.power ?? 10;
        const combined = (this.getStat(team, slotNumber, "pass") + this.getStat(team, partnerSlot, "pass")) / 2;
        let raw = base + combined * this.STAT_COEF;
        raw *= this.positionBonusMultiplier(team, slotNumber, "pass");
        raw *= this.moraleFactor(team, slotNumber);
        return raw;
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

    speedStaminaCostReduction(team, slotNumber) {
        const speedStat = this.getStat(team, slotNumber, "speed");
        return 1.0 - (speedStat / 100) * 0.1;
    }

    // -----------------------------------------------------------
    //   Multiplicateurs contextuels de duel (rôle distinct par stat)
    //   Appliqués au niveau du duel (zone du ballon connue), bornés « subtils ».
    // -----------------------------------------------------------

    /** Facteur de zone normalisé 0..1 (0 = propre tiers, 1 = près du but visé). */
    zoneFactor(zoneIndex) {
        const z = Number(zoneIndex);
        if (!Number.isFinite(z) || MAX_ZONE_INDEX <= 0) return 0;
        return Math.max(0, Math.min(1, z / MAX_ZONE_INDEX));
    }

    /** `attack` : bonus offensif croissant à mesure qu'on progresse vers le but adverse. */
    attackZoneMultiplier(team, slotNumber, zoneIndex) {
        const attackStat = this.getStat(team, slotNumber, "attack");
        return 1.0 + (attackStat / 100) * DUEL_CONTEXT.ATTACK_ZONE_MAX * this.zoneFactor(zoneIndex);
    }

    /** `defense` : bonus défensif croissant à mesure que le ballon menace (zone haute). */
    defenseZoneMultiplier(team, slotNumber, zoneIndex) {
        const defenseStat = this.getStat(team, slotNumber, "defense");
        return 1.0 + (defenseStat / 100) * DUEL_CONTEXT.DEFENSE_ZONE_MAX * this.zoneFactor(zoneIndex);
    }

    /** `speed` : avantage de poursuite — dribble (attaquant), tackle/intercept (défenseur). */
    speedDuelMultiplier(team, slotNumber) {
        const speedStat = this.getStat(team, slotNumber, "speed");
        return 1.0 + (speedStat / 100) * DUEL_CONTEXT.SPEED_DUEL_WEIGHT;
    }

    /** `block` : dernier rempart anti-tir, d'autant plus fort que la défense est basse. */
    blockEdgeMultiplier(team, slotNumber, zoneIndex) {
        const blockStat = this.getStat(team, slotNumber, "block");
        return 1.0 + (blockStat / 100) * DUEL_CONTEXT.BLOCK_EDGE_MAX * this.zoneFactor(zoneIndex);
    }

    // -----------------------------------------------------------
    //   Bases de calcul duel
    // -----------------------------------------------------------
    attackBaseFor(actionKey, team, slotNumber, statBonus = 0) {
        const powerKey = actionKey === "cross" && statBonus > 0 ? "special-cross" : actionKey;
        const base = STATS.attack[powerKey]?.power ?? 10;
        const map  = {
            pass:    { stat: "pass",    bonus: "pass"    },
            dribble: { stat: "dribble", bonus: "dribble" },
            shot:    { stat: "shot",    bonus: "shot"    },
            special: { stat: "attack",  bonus: "attack"  },
        };
        if (actionKey === "cross" || actionKey === "long_pass") {
            const combined = this.crossStat(team, slotNumber) + statBonus;
            let raw = base + combined * this.STAT_COEF;
            raw *= this.positionBonusMultiplier(team, slotNumber, "pass");
            raw *= this.moraleFactor(team, slotNumber);
            return raw;
        }

        const m = map[actionKey] ?? null;
        let raw = base;
        if (m) raw = base + (this.getStat(team, slotNumber, m.stat) + statBonus) * this.STAT_COEF;
        if (m) raw *= this.positionBonusMultiplier(team, slotNumber, m.bonus);
        raw *= this.moraleFactor(team, slotNumber);
        return raw;
    }

    defenseBaseFor(defenseAction, defenseTeam, defenseSlotNumber, isKeeper = false, statBonus = 0) {
        const baseField = STATS.defenseField[defenseAction]?.power;
        const baseGk    = STATS.defenseGK[defenseAction]?.power;
        const base      = baseField ?? baseGk ?? 10;

        if (isKeeper) {
            const mapGK   = { hands: "hand_save", punch: "punch_save", "gk-special": "defense" };
            const statKey = mapGK[defenseAction] ?? null;
            let raw = base + ((statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) : 0) + statBonus) * this.STAT_COEF;
            raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, "gk");
            raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
            return raw;
        }

        if (defenseAction === "heading") {
            const combined = this.headingDuelStat(defenseTeam, defenseSlotNumber);
            let raw = base + combined * this.STAT_COEF;
            raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, "defend");
            raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
            return raw;
        }

        const mapField = { block: "block", intercept: "intercept", tackle: "tackle", "field-special": "defense" };
        const statKey  = mapField[defenseAction] ?? null;
        let raw = base + ((statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) : 0) + statBonus) * this.STAT_COEF;
        const bonusTag = (defenseAction === "block") ? "block" : "defend";
        raw *= this.positionBonusMultiplier(defenseTeam, defenseSlotNumber, bonusTag);
        raw *= this.moraleFactor(defenseTeam, defenseSlotNumber);
        return raw;
    }
}

// -----------------------------------------------------------
//   Helper special moves (hors classe, utilisé par resolvers)
// -----------------------------------------------------------
const KEEPER_ACTION_BY_STAT = { hand_save: "hands", punch_save: "punch" };

function specialStatBonus(move, team, slotNumber, roster) {
    const globalStat = move.mode === "defense" ? "defense" : "attack";
    const bonus = Math.round(roster.getStat(team, slotNumber, globalStat) * 0.15);
    return Math.max(2, Math.min(6, bonus));
}

export function specialActionStat(move, team, slotNumber, roster) {
    const baseAction = String(move.base_action || "").trim();
    const aliases = { hands: "hand_save", punch: "punch_save" };
    const bonus = specialStatBonus(move, team, slotNumber, roster);
    if (baseAction === "cross") {
        return Math.round(roster.crossStat(team, slotNumber) + bonus);
    }
    return roster.getStat(team, slotNumber, aliases[baseAction] ?? baseAction) + bonus;
}

export function specialBaseFor(move, team, slotNumber, roster) {
    if (!move) return 0;
    const baseAction = String(move.base_action || "").trim();
    const bonus = specialStatBonus(move, team, slotNumber, roster);
    if (move.mode !== "defense") {
        return roster.attackBaseFor(baseAction, team, slotNumber, bonus);
    }

    const action = KEEPER_ACTION_BY_STAT[baseAction] ?? baseAction;
    const isKeeper = action === "hands" || action === "punch";
    return roster.defenseBaseFor(action, team, slotNumber, isKeeper, bonus);
}
