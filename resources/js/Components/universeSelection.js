const INTERNATIONALS = ['captain_tsubasa_international', 'world_legends'];

export function universeEnabled(config, key) {
    return config.visible_origins?.[key] !== false
        && (!INTERNATIONALS.includes(key) || config.internationals_visible !== false);
}

export function setUniverseEnabled(config, key, enabled) {
    config.visible_origins ??= {};
    // Convertir l'ancien interrupteur global en choix indépendants sans réactiver l'autre univers.
    if (enabled && INTERNATIONALS.includes(key) && config.internationals_visible === false) {
        for (const origin of INTERNATIONALS) config.visible_origins[origin] = false;
        config.internationals_visible = true;
    }
    config.visible_origins[key] = enabled;
}

export function toggleLegendCountry(config, name, enabled, available) {
    const selected = new Set(config.legends_countries ?? available);
    if (enabled) selected.add(name);
    else selected.delete(name);
    config.legends_countries = [...selected];
}
