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
}
