<?php

/*
| Routes exposées au navigateur par la directive @routes (Ziggy).
| Les routes techniques ne sont jamais envoyées ; celles de la base de données
| de référence (réservées aux admins) ne le sont qu'aux administrateurs.
*/

$technical = ['debugbar.*', 'ignition.*', 'sanctum.*', 'storage.*'];

return [
    // Administrateurs (@routes sans groupe) : tout sauf les routes techniques.
    'except' => $technical,

    'groups' => [
        // Joueurs et visiteurs : ni routes techniques, ni base de référence.
        'player' => array_merge(['*', '!teams.*', '!players.*', '!contracts.*'], array_map(fn ($r) => "!{$r}", $technical)),
    ],
];
