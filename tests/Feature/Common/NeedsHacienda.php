<?php

namespace Tests\Feature\Common;

use App\Models\Hacienda;

trait NeedsHacienda
{
    use NeedsUser {
        setUp as needsUserSetUp;
        getSessionInitializationArray as needsUserGetSessionInitializationArray;
    }

    private Hacienda $hacienda;

    protected function setUp(): void
    {
        $this->needsUserSetUp();

        $this->hacienda = Hacienda::factory()->for($this->user)->create();
    }

    protected function getSessionInitializationArray(): array
    {
        return $this->needsUserGetSessionInitializationArray() + [
            'hacienda_id' => $this->hacienda->id
        ];
    }
}
