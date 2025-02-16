<?php

namespace Tests\Feature\Common;

use App\Models\Finca;

trait NeedsFinca
{
    use NeedsUser {
        setUp as needsUserSetUp;
        getSessionInitializationArray as needsUserGetSessionInitializationArray;
    }

    private Finca $finca;

    protected function setUp(): void
    {
        $this->needsUserSetUp();

        $this->finca = Finca::factory()->for($this->user)->create();
    }

    protected function getSessionInitializationArray(): array
    {
        return $this->needsUserGetSessionInitializationArray() + [
            'finca_id' => $this->finca->id
        ];
    }
}
