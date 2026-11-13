<?php

return [
    // Nombre maximal de sauvegardes par utilisateur (création refusée au-delà).
    'max_saves_per_user' => (int) env('GAME_MAX_SAVES_PER_USER', 20),
];
