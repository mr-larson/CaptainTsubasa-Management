<?php

return [
    // Endurance minimale pour pouvoir s'entraîner
    'min_stamina_to_train' => 10,

    // Coût en stamina par entraînement
    'stamina_cost' => 5,

    // Gain aléatoire min/max sur la stat entraînée
    'gain_min' => 1,
    'gain_max' => 5,

    // Bornes globales des stats
    'stat_min' => 0,
    'stat_max' => 100,

    // Nombre maximum d'entraînements par semaine (par sauvegarde)
    'max_trainings_per_week' => 3,

    // Stats autorisées à l'entraînement
    'allowed_stats' => [
        'speed',
        'stamina',
        'attack',
        'defense',
        'shot',
        'pass',
        'dribble',
        'block',
        'intercept',
        'tackle',
        'hand_save',
        'punch_save',
    ],
];
