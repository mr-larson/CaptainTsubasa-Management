// resources/js/Pages/Match/engine/ui/logEntry.js
// Entrée de l'historique du match (type, couleur, rendu HTML).

const LOG_TYPES = {
    kickoff:           { icon: '🚀', color: 'slate'  },
    'pass-success':    { icon: '✅', color: 'blue'   },
    'pass-failed':     { icon: '❌', color: 'red'    },
    'pass-recovered':  { icon: '✋', color: 'orange' },
    'dribble-success': { icon: '🌀', color: 'blue'   },
    'dribble-failed':  { icon: '❌', color: 'red'    },
    'dribble-face-gk': { icon: '🌀', color: 'blue'   },
    'shot-goal':       { icon: '⚽', color: 'gold'   },
    'shot-saved':      { icon: '🧤', color: 'amber'  },
    'shot-blocked':    { icon: '🧱', color: 'slate'  },
    'shot-recovered':  { icon: '🔄', color: 'slate'  },
    'special-goal':    { icon: '🔥', color: 'gold'   },
    'special-saved':   { icon: '🔥', color: 'amber'  },
    'gk-restart':      { icon: '🥅', color: 'slate'  },
    matchend:          { icon: '🏁', color: 'slate'  },
    injury:            { icon: '🤕', color: 'red'    },
    'card-yellow':     { icon: '🟨', color: 'yellow' },
    'card-red':        { icon: '🟥', color: 'red'    },
    foul:              { icon: '⚠️', color: 'slate'  },
    'free-kick':       { icon: '⚽', color: 'blue'   },
    substitution:      { icon: '🔄', color: 'blue'   },
    simulation:        { icon: '🎭', color: 'amber'  },
    unknown:           { icon: '▸',  color: 'slate'  },
};

let _logIdSeq = 0;

export class LogEntry {
    constructor({ turn = 0, actionType = 'unknown', team = null, result = 'neutral', mainText = '–', details = [], diceTag = null, breakdown = null } = {}) {
        this.id         = String(++_logIdSeq);
        this.turn       = turn;
        this.actionType = actionType;
        this.team       = team;
        this.result     = result;
        this.mainText   = mainText;
        this.details    = details;
        this.diceTag    = diceTag;
        this.breakdown  = breakdown;
    }

    get _cfg() {
        return LOG_TYPES[this.actionType] ?? LOG_TYPES.unknown;
    }

    toHTML() {
        const pad     = n => String(n).padStart(2, '0');
        const isGold  = this._cfg.color === 'gold';
        const badgeBg = isGold ? 'from-amber-600 to-amber-700' : 'from-slate-500 to-slate-600';
        const badge   = `<span class="log-turn-badge inline-flex h-[18px] w-7 shrink-0 items-center justify-center rounded-[5px] bg-gradient-to-br ${badgeBg} text-3xs font-extrabold tracking-[0.3px] text-white shadow">T${pad(this.turn)}</span>`;
        const dotBg   = this.team === 'internal' ? 'bg-blue-500 shadow-[0_0_4px_rgba(59,130,246,0.6)]' : 'bg-orange-500 shadow-[0_0_4px_rgba(249,115,22,0.6)]';
        const dot     = this.team
            ? `<span class="h-[5px] w-[5px] shrink-0 rounded-full ${dotBg}"></span>`
            : '';
        const dice    = this.diceTag
            ? `<span class="whitespace-nowrap rounded bg-slate-400/20 px-1 font-mono text-3xs text-slate-600">${this.diceTag}</span>`
            : '';
        const detail  = this.details.length
            ? `<div class="truncate text-2xs text-slate-500">${this.details.join(' · ')}</div>`
            : '';
        const clickable = this.breakdown ? ' log-entry--clickable group' : '';
        const infoIcon  = this.breakdown
            ? '<span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center self-center rounded-full bg-slate-400/15 text-3xs not-italic text-slate-400 transition-colors group-hover:bg-blue-500/20 group-hover:text-blue-600" title="Voir le détail">ℹ</span>'
            : '';

        return `<li class="log-entry log-result-${this.result} log-${this._cfg.color}${clickable}" data-log-id="${this.id}">
            <div class="flex shrink-0 flex-col items-center gap-[3px]">${badge}${dot}</div>
            <div class="flex min-w-0 flex-1 items-start gap-1.5">
                <span class="log-icon mt-px shrink-0 text-[13px] leading-none">${this._cfg.icon}</span>
                <div class="flex min-w-0 flex-1 flex-col gap-px">
                    <div class="flex flex-wrap items-baseline gap-1 font-semibold text-slate-700"><span>${this.mainText}</span>${dice}</div>
                    ${detail}
                </div>
            </div>
            ${infoIcon}
        </li>`;
    }
}
