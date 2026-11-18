<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Romania
{
    public const NATIONALITY = 'Roumanie';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Gheorghe', 'lastname' => 'Hagi', 'profile' => 'playmaker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Tibor', 'lastname' => 'Selymes', 'profile' => 'wingback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];

    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Alin', 'lastname' => 'Stoica', 'profile' => 'playmaker', 'position' => 'MOC', 'stats' => ['speed' => 69, 'stamina' => 68, 'attack' => 28, 'defense' => 18, 'shot' => 23, 'pass' => 26, 'dribble' => 26, 'heading' => 16], 'note' => 'Milieu offensif roumain d’Anderlecht entre 1996 et 2002, créatif entre les lignes et habile dans les petits espaces.'],
        ['firstname' => 'Helmuth', 'lastname' => 'Duckadam', 'profile' => 'keeper', 'stats' => ['speed' => 57, 'stamina' => 71, 'defense' => 31, 'hand_save' => 29, 'punch_save' => 27], 'note' => 'Le héros de Séville, gardien du Steaua Bucarest vainqueur de la Coupe des champions 1986, célèbre pour ses arrêts lors de la séance de tirs au but.'],
        ['firstname' => 'Dan', 'lastname' => 'Petrescu', 'profile' => 'wingback', 'stats' => ['speed' => 74, 'stamina' => 81, 'defense' => 28, 'pass' => 26, 'cross' => 28], 'note' => 'Arrière droit offensif de la génération 1994, passé par le Genoa et Chelsea ; endurant et disponible dans son couloir.'],
        ['firstname' => 'Gheorghe', 'lastname' => 'Popescu', 'profile' => 'libero', 'secondary' => 'MDC', 'stats' => ['speed' => 65, 'stamina' => 80, 'defense' => 32, 'pass' => 29, 'intercept' => 30, 'heading' => 28], 'note' => 'Défenseur et milieu défensif roumain, ancien capitaine du FC Barcelone ; organise la défense et soigne la relance.'],
        ['firstname' => 'Miodrag', 'lastname' => 'Belodedici', 'profile' => 'libero', 'stats' => ['speed' => 64, 'stamina' => 77, 'defense' => 31, 'pass' => 28, 'intercept' => 33, 'tackle' => 27], 'note' => 'Libéro élégant, champion d’Europe avec le Steaua Bucarest puis l’Étoile rouge de Belgrade ; privilégie l’anticipation au duel physique.'],
        ['firstname' => 'Cristian', 'lastname' => 'Chivu', 'profile' => 'fullback', 'stats' => ['speed' => 70, 'stamina' => 79, 'defense' => 31, 'pass' => 27, 'intercept' => 29, 'tackle' => 28], 'note' => 'Défenseur gaucher capable de jouer à gauche ou dans l’axe, passé par l’Ajax, la Roma et l’Inter ; capitaine de la Roumanie des années 2000.'],
        ['firstname' => 'Ladislau', 'lastname' => 'Bölöni', 'profile' => 'holding', 'stats' => ['speed' => 62, 'stamina' => 80, 'defense' => 29, 'pass' => 27, 'intercept' => 28, 'tackle' => 28], 'note' => 'Milieu expérimenté et discipliné du Steaua champion d’Europe en 1986 ; représenté durant sa carrière de joueur, avant son parcours d’entraîneur.'],
        ['firstname' => 'Dorinel', 'lastname' => 'Munteanu', 'profile' => 'box_to_box', 'stats' => ['speed' => 69, 'stamina' => 83, 'defense' => 26, 'pass' => 28, 'cross' => 27], 'note' => 'Milieu gaucher endurant de la génération dorée, longtemps cadre de la sélection ; relie les lignes et travaille dans le couloir gauche.'],
        ['firstname' => 'Marius', 'lastname' => 'Lăcătuș', 'profile' => 'winger_forward', 'stats' => ['speed' => 75, 'stamina' => 79, 'attack' => 31, 'shot' => 25, 'dribble' => 26, 'defense' => 21], 'note' => 'Attaquant de couloir combatif du Steaua Bucarest ; champion d’Europe en 1986 et membre de la génération roumaine des années 1990.'],
        ['firstname' => 'Adrian', 'lastname' => 'Mutu', 'profile' => 'second_striker', 'stats' => ['speed' => 71, 'stamina' => 73, 'attack' => 33, 'shot' => 28, 'pass' => 26, 'dribble' => 28], 'note' => 'Attaquant technique passé par Parme et la Fiorentina, capable de créer autant que de conclure ; référence offensive de la Roumanie des années 2000.'],
        ['firstname' => 'Nicolae', 'lastname' => 'Dobrin', 'profile' => 'winger', 'position' => 'AG', 'secondary' => 'MOC', 'stats' => ['speed' => 68, 'stamina' => 70, 'attack' => 29, 'pass' => 27, 'dribble' => 28, 'shot' => 23], 'note' => 'Le Prince de Trivale, créateur et dribbleur emblématique du FC Argeș dans les années 1970 ; liberté offensive à gauche et entre les lignes.'],
    ];
}
