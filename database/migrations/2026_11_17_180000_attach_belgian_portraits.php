<?php

use App\Support\PlayerPhotoCredits;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PlayerPhotoCredits::attachMissing();
    }

    public function down(): void
    {
        // Les portraits peuvent être repris par les parties créées depuis l'import.
    }
};
