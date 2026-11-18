<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Instantané des remplacements validés : ne toucher qu'aux portraits
        // importés auparavant, jamais aux images personnelles ni aux archives.
        $replacements = [
            ['Philippe', 'Albert', 'players/philippe-albert.jpg', 'players/philippe-albert-1994.png'],
            ['Michel', 'Preud\'homme', 'players/michel-preudhomme.jpg', 'players/michel-preudhomme-1994.jpg'],
            ['Luc', 'Nilis', 'players/luc-nilis.jpg', 'players/luc-nilis-1998.jpg'],
            ['Marc', 'Wilmots', 'players/marc-wilmots.jpg', 'players/marc-wilmots-1999.jpg'],
            ['Franky', 'Van der Elst', 'players/franky-van-der-elst.jpg', 'players/franky-van-der-elst-1990.jpg'],
            ['Gilles', 'De Bilde', 'players/gilles-de-bilde.jpg', 'players/gilles-de-bilde-1995.jpg'],
            ['Gert', 'Verheyen', 'players/gert-verheyen.jpg', 'players/gert-verheyen-1998.jpg'],
            ['Frank', 'Vercauteren', 'players/frank-vercauteren.jpg', 'players/frank-vercauteren-1980.jpg'],
        ];
        foreach ($replacements as [$firstname, $lastname, $previous, $next]) {
            if (!is_file(public_path('storage/'.$next))) continue;
            DB::table('players')->where('origin', 'world_legends')->whereNull('deleted_at')
                ->where('firstname', $firstname)->where('lastname', $lastname)
                ->where(fn ($q) => $q->whereNull('photo_path')->orWhere('photo_path', '')->orWhere('photo_path', $previous))
                ->update(['photo_path' => $next]);
        }
    }

    public function down(): void
    {
        // Les parties et les références peuvent utiliser les nouveaux chemins.
    }
};
