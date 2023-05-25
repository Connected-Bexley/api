<?php

namespace Tests\Unit\Console\Commands\Ck\AutoDelete;

use App\Console\Commands\Ck\AutoDelete\ServiceRefreshTokensCommand;
use App\Models\ServiceRefreshToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ServiceRefreshTokensTest extends TestCase
{
    public function test_auto_delete_works()
    {
        $newServiceRefreshToken = ServiceRefreshToken::factory()->create([
            'created_at' => Date::today(),
        ]);

        $dueForDeletionServiceRefreshToken = ServiceRefreshToken::factory()->create([
            'created_at' => Date::today()->subMonths(ServiceRefreshToken::AUTO_DELETE_MONTHS),
        ]);

        Artisan::call(ServiceRefreshTokensCommand::class);

        $this->assertDatabaseHas(
            $newServiceRefreshToken->getTable(),
            ['id' => $newServiceRefreshToken->id]
        );
        $this->assertDatabaseMissing(
            $dueForDeletionServiceRefreshToken->getTable(),
            ['id' => $dueForDeletionServiceRefreshToken->id]
        );
    }
}
