<?php

namespace Riclep\ServerpilotDeployer;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Riclep\ServerpilotDeployer\Skeleton\SkeletonClass
 */
class ServerpilotDeployerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'serverpilot-deployer';
    }
}
