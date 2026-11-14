<?php

namespace Tests\Feature;

use App\Http\Middleware\LockGameSave;
use App\Models\GameSaves\GameSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class LockGameSaveMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private array $levels = [];

    private function probeRoutes(): void
    {
        Route::middleware(['web', 'auth', LockGameSave::class])->group(function () {
            Route::get('/_probe/{gameSave}', fn (GameSave $gameSave) => $this->record());
            Route::post('/_probe/{gameSave}', fn (GameSave $gameSave) => $this->record());
        });
    }

    private function record(): string
    {
        $this->levels[] = DB::transactionLevel();

        return 'ok';
    }

    public function test_writes_run_in_an_extra_transaction_but_reads_do_not(): void
    {
        $this->probeRoutes();
        $save = $this->makeSave();
        $user = User::find($save->user_id);
        $base = DB::transactionLevel();

        $this->actingAs($user)->get("/_probe/{$save->id}")->assertOk();
        $this->actingAs($user)->post("/_probe/{$save->id}")->assertOk();

        $this->assertSame([$base, $base + 1], $this->levels);
    }

    public function test_foreign_user_does_not_take_the_lock(): void
    {
        $this->probeRoutes();
        $save  = $this->makeSave();
        $other = User::factory()->create();
        $base  = DB::transactionLevel();

        $this->actingAs($other)->post("/_probe/{$save->id}");

        $this->assertSame([$base], $this->levels);
    }

    /**
     * Une exception levée après une écriture annule cette écriture : Laravel la
     * convertit en réponse avant le middleware, qui doit quand même annuler.
     */
    public function test_a_failing_write_is_rolled_back(): void
    {
        Route::middleware(['web', 'auth', LockGameSave::class])->group(function () {
            Route::post('/_probe/{gameSave}/abort', function (GameSave $gameSave) {
                $gameSave->forceFill(['label' => 'écrit puis annulé'])->save();
                abort(422, 'Refusé après écriture.');
            });
            Route::post('/_probe/{gameSave}/crash', function (GameSave $gameSave) {
                $gameSave->forceFill(['label' => 'écrit puis planté'])->save();
                throw new \RuntimeException('Erreur inattendue.');
            });
            Route::post('/_probe/{gameSave}/ok', function (GameSave $gameSave) {
                $gameSave->forceFill(['label' => 'écrit et gardé'])->save();

                return 'ok';
            });
        });

        $save  = $this->makeSave();
        $user  = User::find($save->user_id);
        $label = $save->label;
        $base  = DB::transactionLevel();

        $this->actingAs($user)->post("/_probe/{$save->id}/abort")->assertStatus(422);
        $this->assertSame($label, $save->fresh()->label);

        $this->actingAs($user)->post("/_probe/{$save->id}/crash")->assertStatus(500);
        $this->assertSame($label, $save->fresh()->label);

        $this->actingAs($user)->post("/_probe/{$save->id}/ok")->assertOk();
        $this->assertSame('écrit et gardé', $save->fresh()->label);

        // Aucune transaction laissée ouverte.
        $this->assertSame($base, DB::transactionLevel());
    }
}
