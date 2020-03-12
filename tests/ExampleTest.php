<?php

namespace Riclep\ServerpilotDeployer\Tests;

use Orchestra\Testbench\TestCase;
use Riclep\ServerpilotDeployer\ServerpilotDeployerServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [ServerpilotDeployerServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
